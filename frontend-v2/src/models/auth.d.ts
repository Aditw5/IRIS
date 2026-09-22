declare namespace Model {
  module Auth {
    interface UserMenu {
      icon: string
      title: string
      url: string
      child: {
        title: string
        url: string
      }
    }
    interface LoginData {
      token: string
      kdProfile: string
      namaUser: string
      kelompokUser: {}
      mapLoginUserToRuangan: []
      pegawai: {}
      profile: {}
    }

    interface UpdateLogo {
      image_logo: string
    }
    interface ProfilePicture {
      profile_picture: string
    }

    interface LegalityStatus {
      legality_status: string
      status: string
    }
  }
}
