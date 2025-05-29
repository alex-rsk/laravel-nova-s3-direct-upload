import Tool from './components/Tool'

Nova.booting((app, store) => {
  app.component('s3-direct-upload', Tool)
})
