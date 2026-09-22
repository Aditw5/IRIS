import { createApp as createClientApp } from 'vue'

import { createHead } from '@vueuse/head'
import { createPinia } from 'pinia'
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';
import { createRouter } from './router'
import Vue from './App.vue'
import './styles'

import { createApi } from '/@src/composable/useApi'
import PrimeVue from 'primevue/config';
import Tooltip from 'primevue/tooltip';
import Toast from 'primevue/toast';
import ConfirmPopup from 'primevue/confirmpopup';
import HighchartsVue from 'highcharts-vue'
const plugins = import.meta.glob('./plugins/*.ts')

export type VueroAppContext = Awaited<ReturnType<typeof createApp>>
export type VueroPlugin = (vuero: VueroAppContext) => void | Promise<void>

// this is a helper function to define plugins with autocompletion
export function definePlugin(plugin: VueroPlugin) {
  return plugin
}
export async function createApp() {
  const app = createClientApp(Vue)
  const router = createRouter()
  const api = createApi()


  const head = createHead()
  app.use(head)
  app.use(PrimeVue);
  app.use(ConfirmationService);
  app.use(ToastService);
  app.use(HighchartsVue)
  app.directive('tooltip-prime', Tooltip);

  const pinia = createPinia()
  app.use(pinia)

  const vuero = {
    app,
    api,
    router,
    head,
    pinia,
  }

  app.provide('vuero', vuero)

  app.component('Toast', Toast);
  app.component('ConfirmPopup ', ConfirmPopup );

  for (const path in plugins) {
    try {
      const { default: plugin } = await plugins[path]()
      await plugin(vuero)
    } catch (error) {
      console.error(`Error while loading plugin "${path}".`)
      console.error(error)
    }
  }

  // use router after plugin registration, so we can register navigation guards
  app.use(vuero.router)


  app.directive("mask-currency", {
    mounted(el) {
      /* Fungsi formatRupiah */
      const formatRupiah = (angka: any, prefix: any) => {
        let number_string = angka.replace(/[^,\d]/g, '').toString(),
          split = number_string.split(','),
          sisa = split[0].length % 3,
          rupiah = split[0].substr(0, sisa),
          ribuan = split[0].substr(sisa).match(/\d{3}/gi),
          separator = '';

        // tambahkan titik jika yang di input sudah menjadi angka ribuan
        if (ribuan) {
          separator = sisa ? '.' : '';
          rupiah += separator + ribuan.join('.');
        }
        rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
        return prefix == undefined ? rupiah : (rupiah ? rupiah : '');
      }
      const convertToRupiah = (angka: any) => {

        let rupiah = '';
        let angkarev = angka.toString().split('').reverse().join('');
        for (let i = 0; i < angkarev.length; i++) if (i % 3 == 0) rupiah += angkarev.substr(i, 3) + '.';
        return rupiah.split('', rupiah.length - 1).reverse().join('');
      }
      el.addEventListener("input", (e: any) => {
        const input = e.target;
        let value = input.value;

        // Update the input value
        input.value = formatRupiah(value, '');;
      });
    },
  });
  return vuero
}
