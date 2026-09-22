import { onMounted, ref } from 'vue';
import { useUserSession } from '/@src/stores/userSession'
import * as qz from 'qz-tray';
import { sha256 } from 'js-sha256';
import { KJUR, KEYUTIL, stob64, hextorstr } from 'jsrsasign';
import sleep from '/@src/utils/sleep'
import { useToaster } from '/@src/composable/toaster'
let toast: any
// onMounted(async () => {
const init = async () => {
  try {
    await qz.security.setCertificatePromise((resolve: any, reject: any) => {
      fetch("/plugins/qztray/digital-certificate.txt", { cache: 'no-store', headers: { 'Content-Type': 'text/plain' } })
        .then(data => resolve(data.text()));
    });
    qz.security.setSignatureAlgorithm("SHA512"); // Since 2.1
    qz.security.setSignaturePromise((hash: any) => {
      return (resolve: any, reject: any) => {
        fetch("/plugins/qztray/key.pem", { cache: 'no-store', headers: { 'Content-Type': 'text/plain' } })
          .then(wrapped => wrapped.text())
          .then(data => {
            var pk = KEYUTIL.getKey(data);
            var sig = new KJUR.crypto.Signature({ "alg": "SHA512withRSA" }); // Use "SHA1withRSA" for QZ Tray 2.0 and older
            sig.init(pk);
            sig.updateString(hash);
            var hex = sig.sign();
            console.log("DEBUG: \n\n" + stob64(hextorstr(hex)));
            resolve(stob64(hextorstr(hex)));
          })
          .catch(err => console.error(err));
      };
    });

    qz.api.setSha256Type((data: any) => sha256(data));
    qz.api.setPromiseType((resolver: any) => new Promise(resolver));

    // connect()
  } catch (error) {
    if(toast !=''){
      toast.info(error, 'Error initializing QZ-Tray')
    }

    console.info('Error initializing QZ Tray:', error);
  }
}
const onLoad = async () => {
  await sleep(1000)

  init()
  await sleep(2000)
  try{
    toast = useToaster()
  } catch (error) {

  }
}
// });
const connect = () => {
  return new Promise(function (resolve, reject) {
    if (qz.websocket.isActive()) {	// if already active, resolve immediately
      resolve;
    } else {
      // const config = { host: '172.20.10.8', usingSecure: false }; // jika host beda
      // try to connect once before firing the mimetype launcher
      // qz.websocket.connect(config).then(resolve, function retry() {
      qz.websocket.connect().then(resolve, function retry() { //teu pake host
        // if a connect was not successful, launch the mimetime, try 3 more times
        window.location.assign("qz:launch");
        qz.websocket.connect({ retries: 2, delay: 1 }).then(resolve, reject);
      });
    }
  });
}
const disconnect = () => {

  if (qz.websocket.isActive()) {
    qz.websocket.disconnect().then((res: any) => console.log(res)).catch((err: any) => console.log(err));
  } else {
    console.log('Error');
  }
}


// Get the list of printers connected

// Print data to chosen printer
const printData = async (url: any, papertipe: string = "A4", copies: number = 1,view:any = false
) => {
  if(view == true){
    console.log('view')
    window.open(import.meta.env.VITE_API_BASE_URL + url
      + "&user=" + useUserSession().getUser().pegawai.namaLengkap
      + '&kdprofile=' + useUserSession().getProfile().id
      + "&token=" + useUserSession().token, "_blank");
      return
  }
  const config = await setConfig(copies, papertipe)
  fetch(import.meta.env.VITE_API_BASE_URL + url, { /*method: 'POST'*/method: 'GET', headers: { 'token': useUserSession().token } })
    // .then((response) => response.arrayBuffer())
    .then((response) => response.blob())
    .then(async (blob) => {

      // debugger
      // ARRAY BUFFER
      // let binary = '';
      // const bytes = new Uint8Array(blob);
      // const len = bytes.byteLength;
      // for (let i = 0; i < len; i++) {
      //   binary += String.fromCharCode(bytes[i]);
      // }
      // const base64String = window.btoa(binary);
      //   let datas = [{
      //     type: 'pixel',
      //     format: 'pdf',
      //     flavor: 'base64',
      //     data: base64String
      // }];


      async function blobToBase64(blob: any) {
        return new Promise((resolve, reject) => {
          const reader = new FileReader();
          reader.onload = () => resolve(reader.result.split(',')[1]);
          reader.onerror = reject;
          reader.readAsDataURL(blob);
        });
      }
      const base64String = await blobToBase64(blob);
      let datas: any = []
      if (blob.type == 'application/pdf') {
        datas = [{
          type: 'pixel',
          format: 'pdf',
          flavor: 'base64', // or 'plain' if the data is raw HTML
          data: base64String,
          options: { altFontRendering: true,ignoreTransparency: true }
        }];
      } else {
        datas = [{
          type: 'pixel',
          format: 'html',
          flavor: 'base64', // or 'plain' if the data is raw HTML
          data: base64String,
          options: { altFontRendering: true,ignoreTransparency: true }
        }];
      }


      // console.log(config)
      // console.log(base64String)
      // const printer = 'Microsoft Print to PDF'
      // const options = { size: { width: 210, height: 100 }, units: 'mm', orientation: 'landscape', margins: { top: 3, right: 1, bottom: 1, left: 5 }, copies: 1 };
      // const config = qz.configs.create(printer, options);
      try {
        qz.print(config, datas).catch((err: any) => {
          console.log(err)
          toast.error(err.message, 'QZ-Tray')
          const _url = window.URL.createObjectURL(blob);
          window.open(_url, '_blank').focus();

        });
      } catch (error) {
        const _url = window.URL.createObjectURL(blob);
        window.open(_url, '_blank').focus();

      }
    }).catch((err) => {

      toast.error(err.message, 'QZ-Tray')
      console.log(err);
    });


}
const getDeviceName = async () => {
  try {

    let res =  await qz.networking.devices()
    for(let i = 0; i < res.length; i++) {
      let info = res[i];
      return info.hostname
    }
  } catch (error) {
     console.log(error);
     return ''
  }
  // }).catch((err :any) =>{
  //   console.log(err)
  //   return ''
  // });
}
const setConfig = async (copies: any, papertipe: any) => {
  let printer = ''
  let options: { size: { width: number; height: number; } | { width: number; height: number; } | { width: number; height: number; } | { width: number; height: number; } | { width: number; height: number; }; units: string; orientation: string; margins: { top: number; right: number; bottom: number; left: number; } | { top: number; right: number; bottom: number; left: number; } | { top: number; right: number; bottom: number; left: number; } | { top: number; right: number; bottom: number; left: number; } | { top: number; right: number; bottom: number; left: number; }; copies: number; };
  let deviceNames = await getDeviceName()
  let response = await fetch(import.meta.env.VITE_API_BASE_URL + `general/printer?device=${deviceNames}&namaexternal=${papertipe}`, { method: 'GET', headers: { 'token': useUserSession().token } })

  if (!response.ok) {
    toast.info('Gagal Mencari settingan Printer', 'QZ-Tray')
    return
  }

  const con = await response.json();

  if (con.response.data.length == 0) {
    toast.info('Master Printer Belum disetting', 'QZ-Tray')
    return
  }
  let configur = con.response.data[0]
  options = {
    size:
    {
      width: configur.width != null ? configur.width : 210,
      height: configur.height != null ? configur.height : 297,
    },
    units: 'mm',
    orientation: configur.orientation ? configur.orientation : 'portrait',
    margins: { top: 1, right: 1, bottom: 1, left: 1 },
    copies: copies
  };
  if (configur.printerdefault) {
    printer = configur.printerdefault
  }
  const config = qz.configs.create(printer, options);
  return config
  // let printername = "";
  // if (printer == 'ETIKET-PUTIH' || printer == 'ETIKET-BIRU') {
  //   printername = printer;
  //   options = { size: { width: 65, height: 40 }, units: 'mm', orientation: 'portrait', margins: { top: 1, right: 1, bottom: 1, left: 1 }, copies: copies };
  // }
  // else if (printer == 'STRUK-RESEP') {
  //   printername = printer;
  //   options = { size: { width: 100, height: 140 }, units: 'mm', orientation: 'portrait', margins: { top: 3, right: 1, bottom: 1, left: 5 }, copies: copies };
  // }
  // else if (printer == 'STRUK-KASIR') {
  //   printername = printer;
  //   if (papertipe == '210280') {
  //     options = { size: { width: 210, height: 280 }, units: 'mm', orientation: 'portrait', margins: { top: 3, right: 1, bottom: 1, left: 5 }, copies: copies };
  //   }
  //   else if (papertipe == '210140') {
  //     options = { size: { width: 210, height: 140 }, units: 'mm', orientation: 'landscape', margins: { top: 3, right: 1, bottom: 1, left: 5 }, copies: copies };
  //   }
  // }
  // else if (printer == 'SEP') {
  //   printername = printer;
  //   options = { size: { width: 210, height: 100 }, units: 'mm', orientation: 'portrait', margins: { top: 3, right: 1, bottom: 1, left: 5 }, copies: copies };
  // }
  // else if (printer == 'STRUK-ANTRIAN') {
  //   printername = printer;
  //   if (papertipe == "7242") {
  //   options = { size: { width: 72, height: 42 }, units: 'mm', orientation: 'portrait', margins: { top: 3, right: 1, bottom: 1, left: 5 }, copies: copies };

  //   } else if (papertipe == "7282") {
  //     options = { size: { width: 72, height: 82 }, units: 'mm', orientation: 'portrait', margins: { top: 3, right: 1, bottom: 1, left: 5 }, copies: copies };
  //   }
  // }
  // else if (printer == 'OFFICE') {
  //   printername = printer;
  //   if (papertipe == "F4") {
  //     options = { size: { width: 215, height: 330 }, units: 'mm', orientation: 'portrait', margins: { top: 3, right: 1, bottom: 1, left: 5 }, copies: copies };

  //   } else if (papertipe == "A5") {
  //     options = { size: { width: 210, height: 148 }, units: 'mm', orientation: 'landscape', margins: { top: 3, right: 1, bottom: 1, left: 5 }, copies: copies };
  //   }
  //   else if (papertipe == "SEP") {
  //     options = { size: { width: 210, height: 100 }, units: 'mm', orientation: 'landscape', margins: { top: 3, right: 1, bottom: 1, left: 5 }, copies: copies };
  //   }
  // }
  // else {
  //   printername = "OFFICE";
  //   if (papertipe == "F4") {
  //     options = { size: { width: 215, height: 330 }, units: 'mm', orientation: 'portrait', margins: { top: 3, right: 1, bottom: 1, left: 5 }, copies: copies };

  //   } else if (papertipe == "A5") {
  //     options = { size: { width: 210, height: 148 }, units: 'mm', orientation: 'landscape', margins: { top: 3, right: 1, bottom: 1, left: 5 }, copies: copies };
  //   }
  //   else if (papertipe == "SEP") {
  //     options = { size: { width: 210, height: 100 }, units: 'mm', orientation: 'landscape', margins: { top: 3, right: 1, bottom: 1, left: 5 }, copies: copies };
  //   }
  //    if (papertipe == "ANTRIAN") {
  //       options = { size: { width: 180, height:210  }, units: 'mm', orientation: 'portrait', margins: { top: 3, right: 1, bottom: 1, left: 5 }, copies: copies };
  //    }

  // }
  // const config = qz.configs.create(printer, options);
  // return config
}


const sendKeysAlttab = () => {
  qz.printers.sendKeysAlttab().then((res: any) => console.log(res)).catch((err: any) => console.log(err));
}
onLoad()
export { connect, printData, qz, sendKeysAlttab, disconnect };
