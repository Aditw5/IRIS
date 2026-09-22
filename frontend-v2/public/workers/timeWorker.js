let timeServer = Date.now();

self.onmessage = (event) => {
  if (event.data && event.data.timeServer) {
    timeServer = event.data.timeServer;
  }
};

setInterval(() => {
  timeServer += 1000; 
  const currentDate = new Date(timeServer);
  const hours = currentDate.getHours().toString().padStart(2, '0');
  const minutes = currentDate.getMinutes().toString().padStart(2, '0');
  const seconds = currentDate.getSeconds().toString().padStart(2, '0');

  self.postMessage(`${hours}:${minutes}:${seconds} WIB`);
}, 1000);
