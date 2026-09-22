export type GroupNotif =
  | 'primary'
  | 'secondary'
  | 'info'
  | 'success'
  | 'warning'
  | 'danger'
export interface Notification {
  norec: string
  judul?: string
  jenis?: string
  idRuanganAsal?: number
  idRuanganTujuan?: number
  ruanganAsal?: string
  ruanganTujuan?: string
  kelompokUser?: string
  idKelompokUser?: number
  idPegawai?: number
  dataArray?: []
  urlForm?: string
  params?: string
  group?: GroupNotif
  namaFungsiFrontEnd?: string
  tgl?: string
  tgl_string?: string
}
