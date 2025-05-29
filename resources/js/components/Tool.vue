<template>
  <div class="relative overflow-hidden bg-white dark:bg-gray-800 rounded-lg shadow mt-3 py-2 px-6 divide-y divide-gray-100 dark:divide-gray-700">
    <div class="space-y-2 md:flex @md/modal:flex md:flex-row @md/modal:flex-row md:space-y-0 @md/modal:space-y-0 py-5"
      :class="$attrs.class">
      <div class="w-1/4 px-6 md:mt-2 @md/modal:mt-2 md:px-8 @md/modal:px-8 md:w-1/4 @md/modal:w-1/4">
        <label>  {{ title }}</label>
      </div>
      <div class="w-3/4 space-y-2 px-6 md:px-8 @md/modal:px-8 md:w-3/4 @md/modal:w-3/4">
        <div class="flex items-center">
          <div class="w-1/3 justify-center flex content-center">
              <label :for="`${prefix}_large_upload`" class="custom-file-upload">
                  <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M10 15H14M12 13V17M13 3H8.2C7.0799 3 6.51984 3 6.09202 3.21799C5.71569 3.40973 5.40973 3.71569 5.21799 4.09202C5 4.51984 5 5.0799 5 6.2V17.8C5 18.9201 5 19.4802 5.21799 19.908C5.40973 20.2843 5.71569 20.5903 6.09202 20.782C6.51984 21 7.0799 21 8.2 21H15.8C16.9201 21 17.4802 21 17.908 20.782C18.2843 20.5903 18.5903 20.2843 18.782 19.908C19 19.4802 19 18.9201 19 17.8V9M13 3L19 9M13 3V7.4C13 7.96005 13 8.24008 13.109 8.45399C13.2049 8.64215 13.3578 8.79513 13.546 8.89101C13.7599 9 14.0399 9 14.6 9H19" stroke="#aaa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
                  <input type="file" :id="`${prefix}_large_upload`" @change="handleFileChoice" />
              </label>
          </div>
          <div class="w-full flex content-center" :id="`${prefix}_upload_visuals`">
            <div v-show="processing" class="progress-bar">
                <div ref="ft" class="file-title">&nbsp;</div>
                <div ref="pg" class="progress-bar-inner"></div>
            </div>
            <div v-show="processingChunked" class="chunked-visualizer">
                <div class="chunk" id="chunk_0"></div>
            </div>
            <div v-show="!processing" class="s3-key" ref="sk">&nbsp;
                <div> {{ currentFieldValue }}</div>
             <!--   <a href="javascript:void(0);" @click.prevent="handleDownload" target="_blank">Download</a> -->
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref } from "vue";
import axios from "axios";


//const BURST_THRESHOLD_BYTES = 100;
//TODO сделать вариабельным (из параметры или вычислять)
//const CHUNK_SIZE = 5 * 1024 * 1024 + 1;

export default {

  setup() {
    const progressBar = ref(null);
    return {
      progressBar
    }
  },
  props: ['index', 'resource', 'resourceName', 'fieldName', 'resourceId', 'field', 'panel', 'postProcessAction'],
  data() {
    return {
      processing: false,
      processingChunked: false,
      chunked: false,
      progress: 0,
      currentFile: '',
      currentFieldValue: '',
      chunkedTransferThreshold : 10 * 1024 * 1024,
      chunksInBurst : 10,
      burstSize :  100 * 1024 * 1024
    }
  },
  updated : function() {

  },
  computed: {
    title() {
      return this.panel.fields[0].title;
    },
    fieldName() {
      return this.panel.fields[0].fieldName;
    },
    prefix() {
      return this.panel.fields[0].fieldName+this.resourceId;
    },    

  },
  mounted() {
    this.refreshResource();
    console.log('postProcessAction', this.panel.fields[0].postProcessAction);
  },

  methods: {

    prepareProgress(childrenCount) {
      let selector = '#' + this.prefix + '_upload_visuals .chunked-visualizer';
      const containerEl = document.querySelector(selector);
      containerEl.innerHTML = '';

      for (let i = 0; i < childrenCount; i++) {
          let chunkEl = document.createElement('div');
          chunkEl.className = 'chunk_progress';
          chunkEl.dataset.index = i;
          let fillEl = document.createElement('div');
          fillEl.className = 'chunk_fill';
          fillEl.style.height='0%';
          chunkEl.appendChild(fillEl);
          containerEl.appendChild(chunkEl);
          chunkEl.style.width = (100 / childrenCount).toString()+'%';
      }
    },

    finishProgress() {
      let selector = '#' + this.prefix + '_upload_visuals .chunked-visualizer';
      const containerEl = document.querySelector('.chunked-visualizer');
      containerEl.querySelectorAll('.chunk_fill').forEach((e, i) => e.style.height = '100%');
    }
    ,
    paintProgress(burstIndex, chunkIndex, percent) {
      let selector = '#' + this.prefix + '_upload_visuals .chunk_progress[data-index="' + burstIndex + '"]';      
      const containerEl = document.querySelector(selector);      
      const fillEl = containerEl.querySelector('.chunk_fill');
      fillEl.style.height = parseInt(fillEl.style.height) + (100/this.chunksInBurst)*(percent/100) + '%';
    },

    async handleFileChoice(event) {
      if (event.target.files.length == 0) {
        return false;
      }

      let self = this;
      let file = event.target.files[0];

      let params = {
        filename: file.name,
        size: file.size,
        type: file.type
      };

      self.currentFile = file.name;

      if (file.size > this.chunkedTransferThreshold) {

        let burstCount = Math.ceil(file.size / this.burstSize);
        let chunkSize = Math.ceil(this.burstSize / this.chunksInBurst);
        this.prepareProgress(burstCount);
        self.processingChunked = true;
        const uploadPromises = [];
        const parts = [];

        const respCu = await Nova.request().post('/nova-vendor/s3-direct-upload/create-chunked-upload', params).then((response) => response);
        const uploadId = respCu.data.uploadId
        let currentPosition = 0;
        let partCounter = 1;
        for (let i = 0; i < burstCount; i++) {
          let initialBurstPosition = currentPosition;
          console.log('Burst #:' + i);
          for (let k = 0; k < this.chunksInBurst; k++) {
            const start = initialBurstPosition + (k * chunkSize);
            const end = start + chunkSize;
            const chunk = file.slice(start, end);
            const length = chunk.size;

            if (chunk.size === 0) {
              console.log('Chunk is empty');
              break;
            }
            currentPosition += length;

            const response = await Nova.request().post('/nova-vendor/s3-direct-upload/presign-chunked', {
              filename: file.name,
              size: length,
              type: file.type,
              filename: file.name,
              upload_id: uploadId,
              part_number: partCounter
            });

            const uploadPromise = axios.put(response.data.url, chunk, {              
              headers: {
                "Content-Type" : "video/mp4"
              },
              onUploadProgress: function (progressEvent) {
                var percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                self.$refs.pg.style.width = percentCompleted + "%";
                self.paintProgress(i, k, percentCompleted);
              }
            }).then((response) => {
              /*if (!response.ok) {
                throw new Error('Upload failed');
              }*/              
              const etag = response.headers.get('ETag');
              parts.push({ ETag: etag, PartNumber: (i * this.chunksInBurst) + (k + 1) });
            });

            partCounter++;
            uploadPromises.push(uploadPromise);

          }

          await Promise.all(uploadPromises);
        }
        
        Nova.request().post('/nova-vendor/s3-direct-upload/complete', {
          'uploadId': uploadId,
          'key' : file.name,
          "parts" : parts
        }).then((response) => {
            console.log('Complete multipart upload response:', response);
            self.finishProgress();
            self.processingChunked = false;
            if (response.status === 200) {
              const params = {
                resourceName: self.resourceName,
                resourceId: self.resourceId,
                fieldName: self.panel.fields[0].fieldName,
                fieldValue: self.currentFile
              };
              Nova.request().post('/nova-vendor/s3-direct-upload/resource', params).then((response) => {
                if (this.panel.fields[0].postProcessAction) {
                  Nova.request().post(this.panel.fields[0].postProcessAction, { 'resourceId': self.resourceId });
                }
              });
              Nova.success('Upload succeeded');
              this.currentFieldValue = self.currentFile;
            } else {
                Nova.error('Upload failed');
            }
        })
      } else {
        self.processing = true;
        Nova.request().post('/nova-vendor/s3-direct-upload/presign', params)
          .then((response) => self.sendFile(file, response));
      }
      self.$refs.ft.innerHTML = file.name;
    }
    ,
    async handleDownload() {
      let self = this;
      let params = {
        key: self.currentFile
      };

      Nova.request().post('/nova-vendor/s3-direct-upload/get-download-link', params)
        .then((response) => console.log(response.data));
    }
    ,
    sendFile(file, response) {
      let self = this;
      self.$refs.pg.style.width = "0%";
      this.processing = true;
      Nova.request().put(response.data.url, file, {
        headers: {
          "Content-Type": file.type,
        },
        onUploadProgress: function (progressEvent) {
          var percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
          self.$refs.pg.style.width = percentCompleted + "%";
        }
      }).then((response) => {
        this.processing = false;
        if (response.status === 200) {
          const params = {
            resourceName: self.resourceName,
            resourceId: self.resourceId,
            fieldName: self.panel.fields[0].fieldName,
            fieldValue: self.currentFile
          };
          Nova.request().post('/nova-vendor/s3-direct-upload/resource', params).then((response) => {
            console.log('Save response', response);
          }).then(() => {
            if (this.panel.fields[0].postProcessAction) {
               console.log('Sending post process request ');
               Nova.request().post(this.panel.fields[0].postProcessAction, { 'resourceId': self.resourceId });
            } 
          });
          Nova.success('Upload succeeded');
          console.log('Post process:', this.panel.fields[0].postProcessAction);
          this.currentFieldValue = self.currentFile;          
        } else {
          Nova.error('Upload failed');
        }
      });
    }
    ,
    refreshResource() {
      let self = this;
      let urlPart = this.resourceName + '/' + this.resourceId + '/' + this.panel.fields[0].fieldName;
      Nova.request().get('/nova-vendor/s3-direct-upload/resource/'+ urlPart)
        .then(function (response) {
          self.currentFieldValue = response.data.data[self.panel.fields[0].fieldName];
        });
    }
    ,
    setNewURL(url) {
      let self = this;
      let params = {
          resourceName: this.resourceName,
          resourceId:   this.resourceId,
          fieldName: this.panel.fields[0].fieldName,
          fieldValue: url
      };
      Nova.request().post('/nova-vendor/s3-direct-upload/resource', params).then(function (response) {
          this.refreshResource();
      });
    }
  }
  ,
  async getDownloadLink(key) {
      const response = await Nova.request().post('/nova-vendor/s3-direct-upload/presign-download', {
        filename: file.name,
        type: file.type,
      });
  }
}
</script>
<style>

.file-title {
  text-align: center;
  float:left;
  width:100%;
  padding-top:3px
}

.progress-bar {
  width:100%;
  background-color:none;
  height:28px;
}

.chunked-visualizer {
  width:100%;
  height:40px;
}

.progress-bar-inner {
  width:1%;
  height:100%;
  background-color:blue;
  text-align:center;
  color:#888;
}

input[type="file"] {
  display: none;
}

.custom-file-upload {
  display: block;
  height: 64px;
  width : 64px;
  cursor: pointer;
}

.chunked-visualizer {
    display: flex;
    width: 100%;
    height: 60px;
    border: none;
    margin-left:5px;
    margin-right:5px;
}

.chunk_progress {
    position: relative;
    height: 100%;
    border-right: 1px solid white;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
}

.chunk_fill {
    height: 0%;
    width: 100%;
    background-color: blue;
    transition: width 0.3s ease;
}

</style>
