<template>
  <div class="uds-drive-new" @click="closeContextMenu">
    <ConfirmDialog group="uds-drive-confirm" />

    <header class="drive-page-head">
      <div>
        <div class="drive-eyebrow"><h1>{{ props.auditInternal ? 'DOKUMEN AUDIT INTERNAL' : 'ULAB DIGITAL STORAGE' }}</h1></div>
      </div>
      <div class="drive-head-badge">
        <i class="iconify" data-icon="material-symbols:cloud-done-outline-rounded"></i>
        <div>
          <strong>{{ props.auditInternal ? 'Terhubung dengan temuan audit' : 'Tersimpan di U-LAB' }}</strong>
          <!-- <span>Dokumen lama tetap terhubung</span> -->
        </div>
      </div>
    </header>

    <section
      v-for="jenis in jenisList"
      :key="jenis"
      class="drive-space"
      :data-program="jenis.toLowerCase()"
      :data-testid="`drive-section-${jenis.toLowerCase()}`"
    >
      <div class="drive-space-head">
        <div class="drive-program-title">
          <span class="drive-program-mark" :class="`is-${jenis.toLowerCase()}`">
            <i class="iconify" :data-icon="programIcon(jenis)"></i>
          </span>
          <div>
            <h2>{{ props.auditInternal ? jenis : `Program ${jenis}` }}</h2>
            <p>
              {{ sections[jenis].totalActive }} item aktif
              <span aria-hidden="true">·</span>
              {{ sections[jenis].totalTrash }} di sampah
            </p>
          </div>
        </div>

        <div class="drive-head-actions">
          <VButton
            v-if="canEdit(jenis) && !sections[jenis].trash && (!props.auditInternal || sections[jenis].currentFolder !== null)"
            type="button"
            color="primary"
            rounded
            raised
            icon="feather:plus"
            @click.stop="openCreateFolder(jenis)"
          >
            Folder baru
          </VButton>
          <button
            type="button"
            class="drive-icon-button"
            :aria-label="`Refresh Program ${jenis}`"
            :title="`Refresh Program ${jenis}`"
            @click.stop="loadSection(jenis)"
          >
            <i class="iconify" data-icon="material-symbols:refresh-rounded"></i>
          </button>
        </div>
      </div>

      <div class="drive-toolbar">
        <div v-if="canEdit(jenis) && !sections[jenis].trash && (!props.auditInternal || sections[jenis].currentFolder !== null)" class="drive-create-actions">
          <button
            type="button"
            class="drive-action-button"
            :disabled="sections[jenis].uploading"
            @click.stop="openFilePicker(jenis, false)"
          >
            <i class="iconify" data-icon="material-symbols:upload-file-outline-rounded"></i>
            <span>Upload file</span>
          </button>
          <button
            type="button"
            class="drive-action-button"
            :disabled="sections[jenis].uploading"
            @click.stop="openFilePicker(jenis, true)"
          >
            <i class="iconify" data-icon="material-symbols:drive-folder-upload-outline-rounded"></i>
            <span>Upload folder</span>
          </button>
          <button
            v-if="jenis === 'Mutu'"
            type="button"
            class="drive-action-button"
            data-testid="insert-cv-button"
            :disabled="sections[jenis].uploading || sections[jenis].currentFolder === null"
            :title="sections[jenis].currentFolder === null
              ? 'Buka folder tujuan terlebih dahulu'
              : 'Masukkan tautan CV pegawai ke folder ini'"
            @click.stop="openCvDialog(jenis)"
          >
            <i class="iconify" data-icon="material-symbols:person-add-outline-rounded"></i>
            <span>Masukkan CV</span>
          </button>
          <input
            :ref="(el) => setFileInputRef(jenis, el)"
            class="is-hidden"
            type="file"
            multiple
            accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp"
            @change="handlePickedFiles(jenis, $event, false)"
          />
          <input
            :ref="(el) => setFolderInputRef(jenis, el)"
            class="is-hidden"
            type="file"
            multiple
            webkitdirectory
            directory
            accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp"
            @change="handlePickedFiles(jenis, $event, true)"
          />
        </div>

        <form class="drive-search" @submit.prevent="loadSection(jenis)">
          <i class="iconify" data-icon="material-symbols:search-rounded"></i>
          <input
            v-model="sections[jenis].search"
            type="search"
            :placeholder="`Cari di Program ${jenis}`"
            :aria-label="`Cari di Program ${jenis}`"
          />
          <button
            v-if="sections[jenis].search"
            type="button"
            aria-label="Bersihkan pencarian"
            @click.stop="clearSearch(jenis)"
          >
            <i class="iconify" data-icon="material-symbols:close-rounded"></i>
          </button>
        </form>

        <div class="drive-view-actions">
          <button
            type="button"
            class="drive-icon-button"
            :class="{ 'is-active': sections[jenis].view === 'grid' }"
            aria-label="Tampilan grid"
            title="Tampilan grid"
            @click.stop="sections[jenis].view = 'grid'"
          >
            <i class="iconify" data-icon="material-symbols:grid-view-outline-rounded"></i>
          </button>
          <button
            type="button"
            class="drive-icon-button"
            :class="{ 'is-active': sections[jenis].view === 'list' }"
            aria-label="Tampilan daftar"
            title="Tampilan daftar"
            @click.stop="sections[jenis].view = 'list'"
          >
            <i class="iconify" data-icon="material-symbols:view-list-outline-rounded"></i>
          </button>
          <button
            type="button"
            class="drive-trash-button"
            :class="{ 'is-active': sections[jenis].trash }"
            @click.stop="toggleTrash(jenis)"
          >
            <i class="iconify" data-icon="material-symbols:delete-outline-rounded"></i>
            <span>{{ sections[jenis].trash ? 'Kembali ke file' : 'Sampah' }}</span>
          </button>
        </div>
      </div>

      <div v-if="!sections[jenis].trash" class="drive-breadcrumbs" aria-label="Lokasi folder">
        <template v-for="(crumb, index) in sections[jenis].breadcrumbs" :key="`${jenis}-${crumb.id ?? 'root'}`">
          <i
            v-if="index > 0"
            class="iconify drive-breadcrumb-separator"
            data-icon="material-symbols:chevron-right-rounded"
          ></i>
          <button type="button" @click.stop="goToBreadcrumb(jenis, crumb.id)">
            <i v-if="index === 0" class="iconify" data-icon="material-symbols:home-storage-outline-rounded"></i>
            <span>{{ crumb.name }}</span>
          </button>
        </template>
        <span v-if="sections[jenis].search" class="drive-search-caption">
          Hasil pencarian “{{ sections[jenis].search }}”
        </span>
      </div>

      <div v-else class="drive-trash-note">
        <i class="iconify" data-icon="material-symbols:delete-outline-rounded"></i>
        <span>Item di sini masih tersimpan dan dapat dipulihkan ke lokasi sebelumnya.</span>
      </div>

      <div
        class="drive-content"
        :class="{ 'is-dragover': sections[jenis].dragging, 'is-loading': sections[jenis].loading }"
        @dragenter.prevent="onDragState(jenis, true)"
        @dragover.prevent="onDragState(jenis, true)"
        @dragleave.prevent="onDragState(jenis, false)"
        @drop.prevent="handleDrop(jenis, $event)"
      >
        <div v-if="sections[jenis].loading" class="drive-loading">
          <span class="loader is-loading"></span>
          <span>Memuat dokumen…</span>
        </div>

        <div v-else-if="!sections[jenis].items.length" class="drive-empty">
          <span class="drive-empty-icon">
            <i
              class="iconify"
              :data-icon="sections[jenis].trash ? 'material-symbols:delete-outline-rounded' : 'material-symbols:folder-open-outline-rounded'"
            ></i>
          </span>
          <strong>{{ emptyTitle(jenis) }}</strong>
          <p>{{ emptyDescription(jenis) }}</p>
        </div>

        <div v-else-if="sections[jenis].view === 'grid'" class="drive-grid">
          <article
            v-for="driveItem in sections[jenis].items"
            :key="driveItem.id"
            class="drive-card"
            :class="{
              'is-selected': sections[jenis].selected?.id === driveItem.id,
              'is-folder': driveItem.kind === 'folder',
              'is-locked': !canOpenItem(driveItem),
            }"
            tabindex="0"
            role="button"
            :aria-label="driveItem.name"
            :aria-disabled="!canOpenItem(driveItem)"
            :title="!canOpenItem(driveItem) ? 'Folder terkunci: akun Anda tidak memiliki akses' : driveItem.name"
            :data-testid="`drive-item-${jenis.toLowerCase()}-${driveItem.id}`"
            @click.stop="selectItem(jenis, driveItem)"
            @dblclick.stop="openItem(jenis, driveItem)"
            @keydown.enter.prevent="openItem(jenis, driveItem)"
            @contextmenu.prevent.stop="openContextMenu($event, jenis, driveItem)"
          >
            <div class="drive-card-top">
              <span class="drive-file-icon" :class="fileIconClass(driveItem)">
                <i class="iconify" :data-icon="fileIcon(driveItem)"></i>
              </span>
              <span v-if="!canOpenItem(driveItem)" class="drive-lock-badge" title="Tidak memiliki akses">
                <i class="iconify" data-icon="material-symbols:lock-rounded"></i>
              </span>
              <button
                type="button"
                class="drive-more-button"
                aria-label="Tindakan lainnya"
                @click.stop="openMoreMenu($event, jenis, driveItem)"
              >
                <i class="iconify" data-icon="material-symbols:more-vert"></i>
              </button>
            </div>
            <div class="drive-card-name" :title="driveItem.name">{{ driveItem.name }}</div>
            <div class="drive-card-meta">
              <span>{{ itemTypeLabel(driveItem) }}</span>
              <span v-if="driveItem.size">{{ formatFileSize(driveItem.size) }}</span>
            </div>
            <div class="drive-card-date">Diubah {{ formatRelativeDate(driveItem.modified_at) }}</div>
          </article>
        </div>

        <div v-else class="drive-list-wrap">
          <table class="drive-list-table">
            <thead>
              <tr>
                <th>Nama</th>
                <th>Pemilik</th>
                <th>Terakhir diubah</th>
                <th>Ukuran</th>
                <th aria-label="Tindakan"></th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="driveItem in sections[jenis].items"
                :key="driveItem.id"
                :class="{
                  'is-selected': sections[jenis].selected?.id === driveItem.id,
                  'is-locked': !canOpenItem(driveItem),
                }"
                @click.stop="selectItem(jenis, driveItem)"
                @dblclick.stop="openItem(jenis, driveItem)"
                @contextmenu.prevent.stop="openContextMenu($event, jenis, driveItem)"
              >
                <td>
                  <div class="drive-list-name">
                    <span class="drive-file-icon is-small" :class="fileIconClass(driveItem)">
                      <i class="iconify" :data-icon="fileIcon(driveItem)"></i>
                    </span>
                    <i
                      v-if="!canOpenItem(driveItem)"
                      class="iconify drive-list-lock"
                      data-icon="material-symbols:lock-rounded"
                      title="Tidak memiliki akses"
                    ></i>
                    <span :title="driveItem.name">{{ driveItem.name }}</span>
                  </div>
                </td>
                <td>{{ driveItem.owner || '-' }}</td>
                <td>{{ formatDate(driveItem.modified_at) }}</td>
                <td>{{ driveItem.kind === 'folder' ? '—' : formatFileSize(driveItem.size) }}</td>
                <td>
                  <button
                    v-if="props.auditInternal && canOpenItem(driveItem)"
                    type="button"
                    class="drive-more-button"
                    aria-label="Salin tautan"
                    title="Salin tautan"
                    @click.stop="copyAuditDocumentLink(driveItem)"
                  >
                    <i class="iconify" data-icon="material-symbols:link-rounded"></i>
                  </button>
                  <button
                    type="button"
                    class="drive-more-button"
                    aria-label="Tindakan lainnya"
                    @click.stop="openMoreMenu($event, jenis, driveItem)"
                  >
                    <i class="iconify" data-icon="material-symbols:more-vert"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="sections[jenis].dragging" class="drive-drop-overlay">
          <i class="iconify" data-icon="material-symbols:cloud-upload-outline-rounded"></i>
          <strong>Lepaskan file atau folder untuk upload</strong>
          <span>Struktur folder akan dibuat kembali di lokasi yang sedang dibuka.</span>
        </div>
      </div>

      <footer class="drive-space-foot">
        <span>{{ sections[jenis].items.length }} item ditampilkan</span>
        <span v-if="sections[jenis].uploading">
          <i class="iconify" data-icon="eos-icons:three-dots-loading"></i>
          Mengupload {{ sections[jenis].uploadCompleted }}/{{ sections[jenis].uploadTotal }} file
          <template v-if="sections[jenis].uploadCurrentName">
            · {{ sections[jenis].uploadCurrentName }}
          </template>
        </span>
        <span v-else>Klik dua kali untuk membuka · klik kanan untuk aktivitas</span>
      </footer>
    </section>

    <div
      v-if="contextMenu.visible && contextMenu.item"
      class="drive-context-menu"
      :style="{ left: `${contextMenu.x}px`, top: `${contextMenu.y}px` }"
      role="menu"
      data-testid="drive-context-menu"
      @click.stop
    >
      <template v-if="sections[contextMenu.jenis].trash">
        <button
          type="button"
          role="menuitem"
          :disabled="!canOpenItem(contextMenu.item)"
          @click="showActivity(contextMenu.jenis, contextMenu.item)"
        >
          <i class="iconify" data-icon="material-symbols:history-rounded"></i>
          <span>Lihat aktivitas</span>
        </button>
        <button
          v-if="canEdit(contextMenu.jenis) && canOpenItem(contextMenu.item)"
          type="button"
          role="menuitem"
          @click="restoreItem(contextMenu.jenis, contextMenu.item)"
        >
          <i class="iconify" data-icon="material-symbols:restore-from-trash-outline-rounded"></i>
          <span>Pulihkan</span>
        </button>
      </template>

      <template v-else>
        <button
          type="button"
          role="menuitem"
          :disabled="!canOpenItem(contextMenu.item)"
          @click="openItem(contextMenu.jenis, contextMenu.item)"
        >
          <i
            class="iconify"
            :data-icon="!canOpenItem(contextMenu.item)
              ? 'material-symbols:lock-rounded'
              : (contextMenu.item.kind === 'folder' ? 'material-symbols:folder-open-outline-rounded' : 'material-symbols:open-in-new-rounded')"
          ></i>
          <span>{{ !canOpenItem(contextMenu.item) ? 'Folder terkunci' : (contextMenu.item.kind === 'folder' ? 'Buka folder' : 'Buka') }}</span>
        </button>
        <button
          v-if="contextMenu.item.kind === 'file' && canOpenItem(contextMenu.item)"
          type="button"
          role="menuitem"
          @click="downloadItem(contextMenu.item)"
        >
          <i class="iconify" data-icon="material-symbols:download-rounded"></i>
          <span>Download</span>
        </button>
        <button
          v-if="props.auditInternal && canOpenItem(contextMenu.item)"
          type="button"
          role="menuitem"
          @click="copyAuditDocumentLink(contextMenu.item)"
        >
          <i class="iconify" data-icon="material-symbols:link-rounded"></i>
          <span>Salin tautan</span>
        </button>
        <button
          v-if="canEdit(contextMenu.jenis) && contextMenu.item.kind === 'file' && canOpenItem(contextMenu.item)"
          type="button"
          role="menuitem"
          @click="openReplaceFile(contextMenu.jenis, contextMenu.item)"
        >
          <i class="iconify" data-icon="material-symbols:upload-file-outline-rounded"></i>
          <span>Ganti file</span>
        </button>
        <button
          v-if="canOpenItem(contextMenu.item)"
          type="button"
          role="menuitem"
          @click="showActivity(contextMenu.jenis, contextMenu.item)"
        >
          <i class="iconify" data-icon="material-symbols:history-rounded"></i>
          <span>Lihat aktivitas</span>
        </button>
        <button
          v-if="!props.auditInternal && canEdit(contextMenu.jenis) && canOpenItem(contextMenu.item) && ['file', 'folder'].includes(contextMenu.item.kind)"
          type="button"
          role="menuitem"
          @click="openLinkDialog(contextMenu.jenis, contextMenu.item)"
        >
          <i class="iconify" data-icon="material-symbols:link-rounded"></i>
          <span>Hubungkan Daftar Induk Dokumen</span>
        </button>
        <button
          v-if="!props.auditInternal && canManageFolderAccess(contextMenu.item) && contextMenu.item.kind === 'folder'"
          type="button"
          role="menuitem"
          @click="openFolderAccess(contextMenu.jenis, contextMenu.item)"
        >
          <i class="iconify" data-icon="material-symbols:manage-accounts-outline-rounded"></i>
          <span>Atur akses folder</span>
        </button>
        <div
          v-if="canEdit(contextMenu.jenis) && canOpenItem(contextMenu.item) && !contextMenu.item.is_attachment"
          class="drive-context-separator"
        ></div>
        <button
          v-if="canEdit(contextMenu.jenis) && canOpenItem(contextMenu.item) && !contextMenu.item.is_attachment"
          type="button"
          role="menuitem"
          @click="openRename(contextMenu.jenis, contextMenu.item)"
        >
          <i class="iconify" data-icon="material-symbols:edit-outline-rounded"></i>
          <span>Ganti nama</span>
        </button>
        <button
          v-if="canEdit(contextMenu.jenis) && canOpenItem(contextMenu.item) && !contextMenu.item.is_attachment"
          type="button"
          role="menuitem"
          @click="openMove(contextMenu.jenis, contextMenu.item)"
        >
          <i class="iconify" data-icon="material-symbols:drive-file-move-outline-rounded"></i>
          <span>Pindahkan</span>
        </button>
        <button
          v-if="canEdit(contextMenu.jenis) && canOpenItem(contextMenu.item) && !contextMenu.item.is_attachment"
          type="button"
          role="menuitem"
          class="is-danger"
          @click="trashItem(contextMenu.jenis, contextMenu.item)"
        >
          <i class="iconify" data-icon="material-symbols:delete-outline-rounded"></i>
          <span>Pindahkan ke Sampah</span>
        </button>
      </template>
    </div>

    <Dialog
      v-model:visible="folderDialog.visible"
      modal
      header="Folder baru"
      :style="{ width: 'min(460px, 92vw)' }"
      @hide="resetFolderDialog"
    >
      <VField :label="`Nama folder Program ${folderDialog.jenis}`">
        <VControl icon="material-symbols:folder-outline-rounded">
          <input
            v-model="folderDialog.name"
            class="input is-rounded"
            type="text"
            maxlength="180"
            placeholder="Masukkan nama folder"
            @keyup.enter="createFolder"
          />
        </VControl>
      </VField>
      <template #footer>
        <VButton type="button" light dark-outlined @click="folderDialog.visible = false">Batal</VButton>
        <VButton type="button" color="primary" rounded :loading="folderDialog.loading" @click="createFolder">
          Buat folder
        </VButton>
      </template>
    </Dialog>

    <Dialog
      v-model:visible="cvDialog.visible"
      modal
      header="Masukkan CV"
      :style="{ width: 'min(560px, 94vw)' }"
      :closable="!cvDialog.loading && !cvDialog.saving"
      :dismissableMask="!cvDialog.loading && !cvDialog.saving"
      @hide="resetCvDialog"
    >
      <div class="drive-cv-dialog">
        <div class="drive-cv-target">
          <span class="drive-file-icon is-folder">
            <i class="iconify" data-icon="flat-color-icons:folder"></i>
          </span>
          <div>
            <span>CV akan dimasukkan ke folder</span>
            <strong>{{ cvDialog.folderName }}</strong>
            <small>CV tersimpan sebagai tautan web dan dapat dibuka langsung dari UDS.</small>
          </div>
        </div>

        <VField label="Pilih pegawai">
          <VControl icon="feather:user" fullwidth class="prime-auto-select">
            <Dropdown
              v-model="cvDialog.selected"
              :options="cvDialog.options"
              optionLabel="label"
              class="is-rounded"
              data-testid="cv-employee-dropdown"
              placeholder="Pilih CV pegawai"
              style="width: 100%"
              :filter="true"
              showClear
              :loading="cvDialog.loading"
              :disabled="cvDialog.loading || cvDialog.saving"
            />
          </VControl>
        </VField>
      </div>
      <template #footer>
        <VButton
          type="button"
          light
          dark-outlined
          :disabled="cvDialog.saving"
          @click="cvDialog.visible = false"
        >
          Batal
        </VButton>
        <VButton
          type="button"
          color="primary"
          rounded
          data-testid="save-cv-button"
          :loading="cvDialog.saving"
          :disabled="cvDialog.loading || !cvDialog.selected"
          @click="saveCvLink"
        >
          Masukkan CV
        </VButton>
      </template>
    </Dialog>

    <Dialog
      v-model:visible="folderAccessDialog.visible"
      modal
      header="Atur akses folder"
      :style="{ width: 'min(680px, 94vw)' }"
      :closable="!folderAccessDialog.saving"
      :dismissableMask="!folderAccessDialog.saving"
      @hide="resetFolderAccessDialog"
    >
      <div class="drive-access-dialog">
        <div class="drive-access-target">
          <span class="drive-file-icon is-folder">
            <i class="iconify" data-icon="flat-color-icons:folder"></i>
          </span>
          <div>
            <span>Folder Program {{ folderAccessDialog.jenis }}</span>
            <strong>{{ folderAccessDialog.item?.name }}</strong>
            <small>Aturan ini berlaku untuk seluruh file dan subfolder di dalamnya.</small>
          </div>
        </div>

        <div v-if="folderAccessDialog.loading" class="drive-access-loading">
          <span class="loader is-loading"></span>
          <span>Memuat akun pegawai…</span>
        </div>

        <template v-else>
          <div class="drive-access-modes" role="radiogroup" aria-label="Mode akses folder">
            <label
              v-for="option in folderAccessModeOptions"
              :key="option.value"
              class="drive-access-mode"
              :class="{ 'is-selected': folderAccessDialog.mode === option.value }"
            >
              <input v-model="folderAccessDialog.mode" type="radio" :value="option.value" />
              <span class="drive-access-mode-icon">
                <i class="iconify" :data-icon="option.icon"></i>
              </span>
              <span>
                <strong>{{ option.label }}</strong>
                <small>{{ option.description }}</small>
              </span>
            </label>
          </div>

          <div v-if="folderAccessDialog.mode !== 'all'" class="drive-access-users">
            <div class="drive-access-users-head">
              <div>
                <strong>
                  {{ folderAccessDialog.mode === 'selected' ? 'Akun yang diberi akses' : 'Akun yang dikecualikan' }}
                </strong>
                <span>{{ folderAccessDialog.selectedUserIds.length }} akun dipilih</span>
              </div>
              <label class="drive-access-search">
                <i class="iconify" data-icon="material-symbols:search-rounded"></i>
                <input
                  v-model="folderAccessDialog.search"
                  type="search"
                  placeholder="Cari nama atau username"
                />
              </label>
            </div>

            <div v-if="filteredFolderAccessUsers.length" class="drive-access-user-list">
              <label
                v-for="account in filteredFolderAccessUsers"
                :key="account.id"
                class="drive-access-user"
              >
                <input
                  type="checkbox"
                  :checked="folderAccessDialog.selectedUserIds.includes(account.id)"
                  @change="toggleFolderAccessUser(account.id)"
                />
                <span class="drive-access-avatar">{{ accountInitials(account.name) }}</span>
                <span class="drive-access-user-main">
                  <strong>{{ account.name }}</strong>
                  <small>@{{ account.username }}<template v-if="account.group"> · {{ account.group }}</template></small>
                </span>
              </label>
            </div>
            <div v-else class="drive-access-no-user">Akun pegawai tidak ditemukan.</div>
          </div>

          <div class="drive-access-note">
            <i class="iconify" data-icon="material-symbols:info-outline-rounded"></i>
            <span>Hanya <strong>pemilik folder</strong> yang dapat mengubah pengaturan ini.</span>
          </div>
        </template>
      </div>
      <template #footer>
        <VButton
          type="button"
          light
          dark-outlined
          :disabled="folderAccessDialog.saving"
          @click="folderAccessDialog.visible = false"
        >
          Batal
        </VButton>
        <VButton
          type="button"
          color="primary"
          rounded
          :loading="folderAccessDialog.saving"
          :disabled="folderAccessDialog.loading || (folderAccessDialog.mode !== 'all' && !folderAccessDialog.selectedUserIds.length)"
          @click="saveFolderAccess"
        >
          Simpan akses
        </VButton>
      </template>
    </Dialog>

    <Dialog
      v-model:visible="renameDialog.visible"
      modal
      header="Ganti nama"
      :style="{ width: 'min(460px, 92vw)' }"
      @hide="resetRenameDialog"
    >
      <VField label="Nama baru">
        <VControl icon="material-symbols:edit-outline-rounded">
          <input
            v-model="renameDialog.name"
            class="input is-rounded"
            type="text"
            maxlength="180"
            placeholder="Masukkan nama baru"
            @keyup.enter="renameItem"
          />
        </VControl>
      </VField>
      <template #footer>
        <VButton type="button" light dark-outlined @click="renameDialog.visible = false">Batal</VButton>
        <VButton type="button" color="primary" rounded :loading="renameDialog.loading" @click="renameItem">
          Simpan
        </VButton>
      </template>
    </Dialog>

    <Dialog
      v-model:visible="moveDialog.visible"
      modal
      header="Pindahkan item"
      :style="{ width: 'min(560px, 94vw)' }"
      @hide="resetMoveDialog"
    >
      <div class="drive-dialog-caption">
        Pilih lokasi baru untuk <strong>{{ moveDialog.item?.name }}</strong>.
      </div>
      <VField label="Folder tujuan">
        <VControl fullwidth class="prime-auto-select">
          <Dropdown
            v-model="moveDialog.destination"
            :options="moveDialog.options"
            optionLabel="label"
            class="is-rounded"
            placeholder="Pilih folder tujuan"
            style="width: 100%"
            :filter="true"
          />
        </VControl>
      </VField>
      <template #footer>
        <VButton type="button" light dark-outlined @click="moveDialog.visible = false">Batal</VButton>
        <VButton type="button" color="primary" rounded :loading="moveDialog.loading" @click="moveItem">
          Pindahkan
        </VButton>
      </template>
    </Dialog>

    <Dialog
      v-model:visible="replaceDialog.visible"
      modal
      header="Upload revisi baru"
      :style="{ width: 'min(520px, 94vw)' }"
      :closable="!replaceDialog.loading"
      :dismissableMask="!replaceDialog.loading"
      @hide="resetReplaceDialog"
    >
      <div class="drive-revision-upload">
        <div class="drive-revision-target">
          <span class="drive-file-icon" :class="replaceDialog.item ? fileIconClass(replaceDialog.item) : ''">
            <i v-if="replaceDialog.item" class="iconify" :data-icon="fileIcon(replaceDialog.item)"></i>
          </span>
          <div>
            <span>{{ replaceDialog.autoDetected ? 'File dengan nama yang sama ditemukan' : 'Dokumen yang akan diganti' }}</span>
            <strong>{{ replaceDialog.item?.name }}</strong>
            <small>
              {{ replaceDialog.autoDetected
                ? 'Unggahan ini akan dicatat sebagai revisi. File lama tetap tersedia di riwayat.'
                : 'File lama tetap tersedia di riwayat revisi.' }}
            </small>
            <small v-if="replaceDialog.autoDetected && automaticRevisionQueue.total > 1" class="drive-revision-progress">
              File konflik {{ automaticRevisionQueue.total - automaticRevisionQueue.items.length + 1 }} dari
              {{ automaticRevisionQueue.total }}
            </small>
          </div>
        </div>

        <label class="drive-revision-picker">
          <input
            type="file"
            accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp"
            :disabled="replaceDialog.loading"
            @change="handleReplaceFile"
          />
          <i class="iconify" data-icon="material-symbols:upload-file-outline-rounded"></i>
          <span>{{ replaceDialog.file ? 'Pilih file lain' : 'Pilih file revisi' }}</span>
        </label>

        <div v-if="replaceDialog.file" class="drive-revision-selected">
          <i class="iconify" data-icon="material-symbols:description-outline-rounded"></i>
          <div>
            <strong>{{ replaceDialog.file.name }}</strong>
            <span>{{ formatFileSize(replaceDialog.file.size) }}</span>
          </div>
          <button
            type="button"
            title="Batalkan pilihan file"
            :disabled="replaceDialog.loading"
            @click="replaceDialog.file = null"
          >
            <i class="iconify" data-icon="material-symbols:close-rounded"></i>
          </button>
        </div>

        <VField :label="replaceDialog.autoDetected ? 'Keterangan revisi *' : 'Keterangan revisi'">
          <VControl fullwidth>
            <textarea
              v-model="replaceDialog.description"
              data-testid="revision-description"
              class="textarea is-rounded"
              rows="3"
              maxlength="500"
              :placeholder="replaceDialog.autoDetected
                ? 'Wajib isi perubahan pada revisi ini'
                : 'Tuliskan perubahan pada revisi ini (opsional)'"
              :disabled="replaceDialog.loading"
            ></textarea>
          </VControl>
        </VField>
      </div>
      <template #footer>
        <VButton type="button" light dark-outlined :disabled="replaceDialog.loading" @click="cancelReplaceFile">
          Batal
        </VButton>
        <VButton
          type="button"
          color="primary"
          rounded
          :loading="replaceDialog.loading"
          :disabled="replaceDialog.autoDetected && !replaceDialog.description.trim()"
          @click="replaceFile"
        >
          Upload revisi
        </VButton>
      </template>
    </Dialog>

    <Dialog
      v-model:visible="linkDialog.visible"
      modal
      header="Hubungkan Daftar Induk Dokumen"
      :style="{ width: 'min(620px, 94vw)' }"
      :closable="!linkDialog.loading && !linkDialog.saving"
      :dismissableMask="!linkDialog.loading && !linkDialog.saving"
      @hide="resetLinkDialog"
    >
      <div class="drive-link-dialog">
        <div class="drive-link-target">
          <span class="drive-file-icon" :class="linkDialog.item ? fileIconClass(linkDialog.item) : ''">
            <i v-if="linkDialog.item" class="iconify" :data-icon="fileIcon(linkDialog.item)"></i>
          </span>
          <div>
            <span>Target {{ linkDialog.targetType === 'folder' ? 'folder' : 'file' }}</span>
            <strong>{{ linkDialog.item?.name }}</strong>
            <small v-if="linkDialog.targetType === 'folder'">
              Dari Daftar Induk Dokumen, tautan akan membuka lokasi folder ini.
            </small>
            <small v-else>
              Dari Daftar Induk Dokumen, tautan akan langsung membuka file ini.
            </small>
          </div>
        </div>

        <VField label="Nomor induk dokumen">
          <VControl fullwidth class="prime-auto-select">
            <Dropdown
              v-model="linkDialog.selected"
              :options="linkDialog.options"
              optionLabel="label"
              class="is-rounded"
              placeholder="Pilih nomor induk dokumen"
              style="width: 100%"
              :filter="true"
              :loading="linkDialog.loading"
              :disabled="linkDialog.loading"
            />
          </VControl>
        </VField>

        <div v-if="linkDialog.currentLabel" class="drive-link-current">
          <i class="iconify" data-icon="material-symbols:link-rounded"></i>
          <div>
            <span>Saat ini terhubung</span>
            <strong>{{ linkDialog.currentLabel }}</strong>
          </div>
        </div>
      </div>
      <template #footer>
        <VButton
          v-if="linkDialog.currentId"
          type="button"
          color="danger"
          outlined
          rounded
          :disabled="linkDialog.loading"
          @click="unlinkNomorInduk"
        >
          Lepas hubungan
        </VButton>
        <VButton type="button" light dark-outlined :disabled="linkDialog.loading" @click="linkDialog.visible = false">
          Batal
        </VButton>
        <VButton
          type="button"
          color="primary"
          rounded
          :loading="linkDialog.saving"
          :disabled="linkDialog.loading || !linkDialog.selected"
          @click="saveNomorIndukLink"
        >
          Hubungkan
        </VButton>
      </template>
    </Dialog>

    <Dialog
      v-model:visible="activityDialog.visible"
      modal
      header="Aktivitas"
      :style="{ width: 'min(620px, 94vw)' }"
    >
      <div class="drive-activity-head">
        <span class="drive-file-icon" :class="activityDialog.item ? fileIconClass(activityDialog.item) : ''">
          <i v-if="activityDialog.item" class="iconify" :data-icon="fileIcon(activityDialog.item)"></i>
        </span>
        <div>
          <strong>{{ activityDialog.item?.name }}</strong>
          <span>Riwayat perubahan item</span>
        </div>
      </div>

      <div v-if="activityDialog.loading" class="drive-activity-empty">Memuat aktivitas…</div>
      <div v-else-if="!activityDialog.timeline.length" class="drive-activity-empty">Belum ada aktivitas.</div>
      <div v-else class="drive-timeline">
        <div v-for="activity in activityDialog.timeline" :key="activity.id" class="drive-timeline-item">
          <span class="drive-timeline-dot">
            <i class="iconify" :data-icon="activityIcon(activity.action)"></i>
          </span>
          <div class="drive-timeline-main">
            <strong>{{ activityLabel(activity.action) }}</strong>
            <p>{{ activity.user || '-' }}</p>
            <span>{{ formatDate(activity.date) }}</span>
            <p v-if="activity.description" class="drive-timeline-description">
              {{ activity.description }}
            </p>
            <span v-if="activity.can_open" class="drive-timeline-file">
              {{ activity.extension === 'link' ? 'Tautan' : `Dokumen ${String(activity.extension || '').toUpperCase()}` }}
            </span>
          </div>
          <div class="drive-timeline-side">
            <span v-if="activity.metadata?.revision" class="drive-revision-pill">
              Revisi {{ String(activity.metadata.revision).padStart(2, '0') }}
            </span>
            <div v-if="activity.can_open" class="drive-revision-actions">
              <button type="button" title="Buka revisi ini" @click="openActivityRevision(activity)">
                <i class="iconify" data-icon="material-symbols:visibility-outline-rounded"></i>
                <span>Buka</span>
              </button>
              <button
                v-if="activity.can_download"
                type="button"
                title="Download revisi ini"
                @click="downloadActivityRevision(activity)"
              >
                <i class="iconify" data-icon="material-symbols:download-rounded"></i>
                <span>Download</span>
              </button>
            </div>
          </div>
        </div>
      </div>
      <template #footer>
        <VButton
          v-if="activityDialog.item && canEdit(activityDialog.jenis) && activityDialog.item.kind === 'file'"
          type="button"
          color="primary"
          outlined
          rounded
          @click="openReplaceFile(activityDialog.jenis, activityDialog.item, true)"
        >
          <span class="icon">
            <i class="iconify" data-icon="material-symbols:upload-file-outline-rounded"></i>
          </span>
          <span>Upload revisi baru</span>
        </VButton>
        <VButton type="button" light dark-outlined @click="activityDialog.visible = false">Tutup</VButton>
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, reactive } from 'vue'
import { useHead } from '@vueuse/head'
import { useRoute } from 'vue-router'
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'
import { useApi } from '/@src/composable/useApi'
import { useToaster } from '/@src/composable/toaster'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import { useUserSession } from '/@src/stores/userSession'
import * as H from '/@src/utils/appHelper'

const props = withDefaults(defineProps<{ auditInternal?: boolean }>(), {
  auditInternal: false,
})

useHead({ title: (props.auditInternal ? 'Dokumen Audit Internal' : 'U-LAB Digital Storage') + ' - ' + import.meta.env.VITE_PROJECT })
useViewWrapper().setFullWidth(true)
const confirmDialog = useConfirm()

type JenisUdr = 'Mutu' | 'Teknik' | 'Admin' | 'Audit Internal'
type DriveView = 'grid' | 'list'

interface DriveItem {
  id: number
  root_id: number
  parent_id: number | null
  name: string
  kind: 'folder' | 'file' | 'link'
  extension?: string | null
  stored_file?: string | null
  link?: string | null
  description?: string | null
  revision?: number
  modified_at?: string | null
  created_at?: string | null
  owner?: string | null
  owner_id?: number | null
  can_manage_access?: boolean
  size?: number | null
  nomor_induk?: string | null
  nomor_induk_fk?: number | null
  is_attachment?: boolean
  can_access?: boolean
  access_mode?: 'all' | 'selected' | 'excluded'
  is_restricted?: boolean
}

interface FolderAccessUser {
  id: number
  username: string
  name: string
  group?: string | null
}

interface Breadcrumb {
  id: number | null
  name: string
}

interface DriveSectionState {
  items: DriveItem[]
  breadcrumbs: Breadcrumb[]
  currentFolder: number | null
  selected: DriveItem | null
  search: string
  view: DriveView
  trash: boolean
  loading: boolean
  uploading: boolean
  uploadCompleted: number
  uploadTotal: number
  uploadCurrentName: string
  dragging: boolean
  totalActive: number
  totalTrash: number
  canWrite: boolean
}

const jenisList: JenisUdr[] = props.auditInternal ? ['Audit Internal'] : ['Mutu', 'Teknik', 'Admin']
const api = useApi()
const toast = useToaster()
const userSession = useUserSession()
const route = useRoute()

const makeSection = (): DriveSectionState => ({
  items: [],
  breadcrumbs: [],
  currentFolder: null,
  selected: null,
  search: '',
  view: 'list',
  trash: false,
  loading: false,
  uploading: false,
  uploadCompleted: 0,
  uploadTotal: 0,
  uploadCurrentName: '',
  dragging: false,
  totalActive: 0,
  totalTrash: 0,
  canWrite: false,
})

const sections = reactive<Record<JenisUdr, DriveSectionState>>({
  Mutu: makeSection(),
  Teknik: makeSection(),
  Admin: makeSection(),
  'Audit Internal': makeSection(),
})

const fileInputs = reactive<Record<JenisUdr, HTMLInputElement | null>>({
  Mutu: null,
  Teknik: null,
  Admin: null,
  'Audit Internal': null,
})

const folderInputs = reactive<Record<JenisUdr, HTMLInputElement | null>>({
  Mutu: null,
  Teknik: null,
  Admin: null,
  'Audit Internal': null,
})

const contextMenu = reactive<{
  visible: boolean
  x: number
  y: number
  jenis: JenisUdr
  item: DriveItem | null
}>({
  visible: false,
  x: 0,
  y: 0,
  jenis: 'Mutu',
  item: null,
})

const folderDialog = reactive({
  visible: false,
  loading: false,
  jenis: 'Mutu' as JenisUdr,
  name: '',
})

const cvDialog = reactive({
  visible: false,
  loading: false,
  saving: false,
  parentId: null as number | null,
  folderName: '',
  selected: null as any,
  options: [] as any[],
})

const folderAccessDialog = reactive({
  visible: false,
  loading: false,
  saving: false,
  jenis: 'Mutu' as JenisUdr,
  item: null as DriveItem | null,
  mode: 'all' as 'all' | 'selected' | 'excluded',
  selectedUserIds: [] as number[],
  users: [] as FolderAccessUser[],
  search: '',
})

const folderAccessModeOptions = [
  {
    value: 'all',
    label: 'Semua akun',
    description: 'Semua pegawai dapat membuka folder dan seluruh isinya.',
    icon: 'material-symbols:group-outline-rounded',
  },
  {
    value: 'selected',
    label: 'Hanya akun terpilih',
    description: 'Folder hanya dapat dibuka oleh akun pegawai yang dipilih.',
    icon: 'material-symbols:person-check-outline-rounded',
  },
  {
    value: 'excluded',
    label: 'Semua, kecuali…',
    description: 'Semua pegawai dapat membuka folder kecuali akun yang dipilih.',
    icon: 'material-symbols:person-off-outline-rounded',
  },
] as const

const renameDialog = reactive({
  visible: false,
  loading: false,
  jenis: 'Mutu' as JenisUdr,
  item: null as DriveItem | null,
  name: '',
})

const moveDialog = reactive({
  visible: false,
  loading: false,
  jenis: 'Mutu' as JenisUdr,
  item: null as DriveItem | null,
  destination: null as any,
  options: [] as any[],
})

const replaceDialog = reactive({
  visible: false,
  loading: false,
  jenis: 'Mutu' as JenisUdr,
  item: null as DriveItem | null,
  file: null as File | null,
  description: '',
  returnToActivity: false,
  autoDetected: false,
})

const automaticRevisionQueue = reactive({
  active: false,
  jenis: 'Mutu' as JenisUdr,
  folderUpload: false,
  items: [] as Array<{ item: DriveItem; file: File }>,
  newFiles: [] as File[],
  deferredFiles: [] as File[],
  total: 0,
})

const activityDialog = reactive({
  visible: false,
  loading: false,
  jenis: 'Mutu' as JenisUdr,
  item: null as DriveItem | null,
  timeline: [] as any[],
})

const linkDialog = reactive({
  visible: false,
  loading: false,
  saving: false,
  jenis: 'Mutu' as JenisUdr,
  item: null as DriveItem | null,
  targetType: 'file' as 'file' | 'folder',
  selected: null as any,
  options: [] as any[],
  currentId: null as number | null,
  currentLabel: '',
})

const kelompokUser = () =>
  String(userSession.getUser()?.kelompokUser?.kelompokUser ?? '').toLowerCase().trim()

const lokasiKalibrasiFk = () =>
  Number(userSession.getUser()?.kelompokUser?.lokasiKalibrasiFk ?? 0)

const canEdit = (jenis: JenisUdr) => {
  if (jenis === 'Audit Internal') return sections[jenis].canWrite
  const group = kelompokUser()
  if (group === 'asman' || group === 'manager') return true
  if (jenis === 'Mutu') return group === 'mutu' && lokasiKalibrasiFk() === 1
  if (jenis === 'Teknik') return group === 'pelaksana' || group === 'penyelia'
  if (jenis === 'Admin') return group === 'registrasi'
  return false
}

const canManageFolderAccess = (item: DriveItem | null) => Boolean(item?.can_manage_access)
const canOpenItem = (item: DriveItem | null) => Boolean(item) && item?.can_access !== false

const filteredFolderAccessUsers = computed(() => {
  const keyword = folderAccessDialog.search.toLocaleLowerCase('id-ID').trim()
  if (!keyword) return folderAccessDialog.users

  return folderAccessDialog.users.filter((account) =>
    [account.name, account.username, account.group]
      .some((value) => String(value || '').toLocaleLowerCase('id-ID').includes(keyword)),
  )
})

const accountInitials = (name: string) => String(name || '?')
  .trim()
  .split(/\s+/)
  .slice(0, 2)
  .map((part) => part.charAt(0).toUpperCase())
  .join('') || '?'

const programIcon = (jenis: JenisUdr) => {
  if (jenis === 'Audit Internal') return 'material-symbols:fact-check-outline-rounded'
  if (jenis === 'Mutu') return 'material-symbols:verified-outline-rounded'
  if (jenis === 'Teknik') return 'material-symbols:engineering-outline-rounded'
  return 'material-symbols:business-center-outline-rounded'
}

const fileIcon = (item: DriveItem) => {
  if (item.kind === 'folder') return 'flat-color-icons:folder'
  if (item.kind === 'link') return 'material-symbols:link-rounded'

  const extension = String(item.extension || '').toLowerCase()
  if (extension === 'doc' || extension === 'docx') return 'vscode-icons:file-type-word'
  if (extension === 'xls' || extension === 'xlsx') return 'vscode-icons:file-type-excel'
  if (extension === 'pdf') return 'vscode-icons:file-type-pdf2'
  if (['jpg', 'jpeg', 'png', 'webp'].includes(extension)) return 'vscode-icons:file-type-image'
  return 'vscode-icons:default-file'
}

const fileIconClass = (item: DriveItem) => ({
  'is-folder': item.kind === 'folder',
  'is-link': item.kind === 'link',
  'is-word': ['doc', 'docx'].includes(String(item.extension || '').toLowerCase()),
  'is-excel': ['xls', 'xlsx'].includes(String(item.extension || '').toLowerCase()),
  'is-pdf': String(item.extension || '').toLowerCase() === 'pdf',
  'is-image': ['jpg', 'jpeg', 'png', 'webp'].includes(String(item.extension || '').toLowerCase()),
})

const itemTypeLabel = (item: DriveItem) => {
  if (item.kind === 'folder') return 'Folder'
  if (item.kind === 'link') return 'Tautan'
  const type = String(item.extension || 'File').toUpperCase()
  return item.is_attachment ? `Lampiran ${type}` : type
}

const formatFileSize = (size?: number | null) => {
  if (!size) return '—'
  if (size < 1024) return `${size} B`
  if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} KB`
  return `${(size / 1024 / 1024).toFixed(1)} MB`
}

const formatDate = (value?: string | null) => {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return String(value)
  return date.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })
}

const formatRelativeDate = (value?: string | null) => {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return String(value)

  const diff = Date.now() - date.getTime()
  const day = 24 * 60 * 60 * 1000
  if (diff < day && date.getDate() === new Date().getDate()) {
    return `hari ini, ${date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}`
  }
  if (diff < day * 2) return 'kemarin'
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

const loadSection = async (jenis: JenisUdr) => {
  const state = sections[jenis]
  state.loading = true
  state.selected = null
  closeContextMenu()

  try {
    const params = new URLSearchParams({ jenisudr: jenis })
    if (state.currentFolder !== null && !state.trash) params.set('parent_id', String(state.currentFolder))
    if (state.search.trim()) params.set('search', state.search.trim())
    if (state.trash) params.set('trash', 'true')

    const response = await api.get('/udr/new-drive-items?' + params.toString())
    state.items = response?.items ?? []
    state.breadcrumbs = response?.breadcrumbs ?? []
    state.totalActive = Number(response?.total_active ?? 0)
    state.totalTrash = Number(response?.total_trash ?? 0)
    state.canWrite = Boolean(response?.can_write)
  } catch (error: any) {
    state.items = []
    H.alert('error', error?.message ?? `Gagal memuat Program ${jenis}`)
  } finally {
    state.loading = false
  }
}

const reloadAll = async () => {
  await Promise.all(jenisList.map((jenis) => loadSection(jenis)))
}

const clearSearch = async (jenis: JenisUdr) => {
  sections[jenis].search = ''
  await loadSection(jenis)
}

const goToBreadcrumb = async (jenis: JenisUdr, folderId: number | null) => {
  sections[jenis].currentFolder = folderId
  sections[jenis].search = ''
  await loadSection(jenis)
}

const toggleTrash = async (jenis: JenisUdr) => {
  const state = sections[jenis]
  state.trash = !state.trash
  state.currentFolder = null
  state.search = ''
  await loadSection(jenis)
}

const selectItem = (jenis: JenisUdr, item: DriveItem) => {
  sections[jenis].selected = item
  closeContextMenu()
}

const openItem = async (jenis: JenisUdr, item: DriveItem) => {
  closeContextMenu()
  if (!canOpenItem(item)) {
    H.alert('warning', 'Folder ini terkunci. Akun Anda tidak memiliki akses untuk membukanya.')
    return
  }
  if (sections[jenis].trash) {
    await showActivity(jenis, item)
    return
  }

  if (item.kind === 'folder') {
    sections[jenis].currentFolder = item.id
    sections[jenis].search = ''
    await loadSection(jenis)
    return
  }

  H.printBlade(`udr/new-drive-open?id=${item.id}`)
}

const downloadItem = (item: DriveItem) => {
  closeContextMenu()
  if (!canOpenItem(item)) return
  H.printBlade(`udr/new-drive-download?id=${item.id}`)
}

const copyAuditDocumentLink = async (item: DriveItem) => {
  closeContextMenu()
  const query = new URLSearchParams({
    openUdrId: String(item.id),
    targetType: item.kind === 'folder' ? 'folder' : 'file',
    jenisudr: 'Audit Internal',
  })
  const link = `${window.location.origin}/module/mutu/dokumen-audit-internal?${query.toString()}`
  try {
    await navigator.clipboard.writeText(link)
    toast.success('Tautan Dokumen Audit Internal berhasil disalin')
  } catch {
    H.alert('error', 'Tautan tidak dapat disalin oleh browser ini')
  }
}

const openReplaceFile = (
  jenis: JenisUdr,
  item: DriveItem,
  returnToActivity = false,
  selectedFile: File | null = null,
  autoDetected = false,
) => {
  if (!canEdit(jenis) || item.kind !== 'file') return

  closeContextMenu()
  replaceDialog.jenis = jenis
  replaceDialog.item = item
  replaceDialog.file = selectedFile
  replaceDialog.description = autoDetected ? '' : item.description || ''
  replaceDialog.returnToActivity = returnToActivity
  replaceDialog.autoDetected = autoDetected
  if (returnToActivity) activityDialog.visible = false
  replaceDialog.visible = true
}

const resetAutomaticRevisionQueue = () => {
  automaticRevisionQueue.active = false
  automaticRevisionQueue.items = []
  automaticRevisionQueue.newFiles = []
  automaticRevisionQueue.deferredFiles = []
  automaticRevisionQueue.total = 0
  automaticRevisionQueue.folderUpload = false
}

const resetReplaceDialog = () => {
  if (replaceDialog.autoDetected && automaticRevisionQueue.active) {
    resetAutomaticRevisionQueue()
  }
  replaceDialog.loading = false
  replaceDialog.item = null
  replaceDialog.file = null
  replaceDialog.description = ''
  replaceDialog.returnToActivity = false
  replaceDialog.autoDetected = false
}

const cancelReplaceFile = () => {
  replaceDialog.visible = false
}

const handleReplaceFile = (event: Event) => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0] ?? null
  input.value = ''

  if (!file) return

  const extension = file.name.split('.').pop()?.toLowerCase() ?? ''
  const allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'webp']
  if (!allowedExtensions.includes(extension) || file.size > 30 * 1024 * 1024) {
    H.alert('warning', `File ${file.name} tidak didukung atau melebihi 30 MB`)
    return
  }

  replaceDialog.file = file
}

const replaceFile = async () => {
  if (!replaceDialog.item || !replaceDialog.file) {
    H.alert('warning', 'Pilih file revisi terlebih dahulu')
    return
  }
  if (replaceDialog.autoDetected && !replaceDialog.description.trim()) {
    H.alert('warning', 'Keterangan revisi wajib diisi')
    return
  }

  const jenis = replaceDialog.jenis
  const item = replaceDialog.item
  const returnToActivity = replaceDialog.returnToActivity
  const autoDetected = replaceDialog.autoDetected
  const formData = new FormData()
  formData.append('id', String(item.id))
  formData.append('file', replaceDialog.file)
  formData.append('description', replaceDialog.description.trim())

  replaceDialog.loading = true
  try {
    const response = await api.post('/udr/new-drive-replace-file', formData)
    toast.success(`Revisi ${String(response?.revision ?? '').padStart(2, '0')} berhasil diupload`)
    await loadSection(jenis)

    if (autoDetected && automaticRevisionQueue.active) {
      automaticRevisionQueue.items.shift()
      if (automaticRevisionQueue.items.length) {
        const next = automaticRevisionQueue.items[0]
        replaceDialog.item = next.item
        replaceDialog.file = next.file
        replaceDialog.description = ''
        return
      }

      const newFiles = [...automaticRevisionQueue.newFiles]
      const deferredFiles = [...automaticRevisionQueue.deferredFiles]
      const folderUpload = automaticRevisionQueue.folderUpload
      resetAutomaticRevisionQueue()
      replaceDialog.autoDetected = false
      replaceDialog.visible = false

      const uploaded = newFiles.length ? await uploadFilesDirect(jenis, newFiles, folderUpload) : true
      if (uploaded && deferredFiles.length) {
        await uploadFiles(jenis, deferredFiles, folderUpload)
      }
      return
    }

    replaceDialog.visible = false
    if (returnToActivity) await showActivity(jenis, item)
  } catch (error: any) {
    H.alert('error', error?.message ?? 'Gagal mengupload revisi')
  } finally {
    replaceDialog.loading = false
  }
}

const setFileInputRef = (jenis: JenisUdr, element: HTMLInputElement | null) => {
  fileInputs[jenis] = element
}

const setFolderInputRef = (jenis: JenisUdr, element: HTMLInputElement | null) => {
  folderInputs[jenis] = element
}

const openFilePicker = (jenis: JenisUdr, folder: boolean) => {
  if (sections[jenis].uploading) return
  if (folder) folderInputs[jenis]?.click()
  else fileInputs[jenis]?.click()
}

const MAX_UPLOAD_FILE_SIZE = 30 * 1024 * 1024
const UPLOAD_BATCH_MAX_FILES = 15
const UPLOAD_BATCH_MAX_BYTES = 24 * 1024 * 1024
const CONFLICT_CHECK_BATCH_SIZE = 150
const allowedUploadExtensions = new Set(['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'webp'])
const droppedRelativePaths = new WeakMap<File, string>()

const normalizeRelativePath = (path: string, fallback: string) => {
  const normalized = String(path || fallback)
    .replace(/\\/g, '/')
    .replace(/^\/+/, '')
    .split('/')
    .filter((segment) => segment && segment !== '.')
    .join('/')

  return normalized || fallback
}

const relativePathFor = (file: File, folderUpload: boolean) => {
  if (!folderUpload) return file.name
  return normalizeRelativePath(
    droppedRelativePaths.get(file) || (file as any).webkitRelativePath || file.name,
    file.name,
  )
}

const uploadValidationError = (file: File): string | null => {
  const extension = file.name.split('.').pop()?.toLowerCase() ?? ''
  if (!allowedUploadExtensions.has(extension)) {
    return `${file.name} (format tidak didukung)`
  }
  if (file.size > MAX_UPLOAD_FILE_SIZE) {
    return `${file.name} (${formatFileSize(file.size)}, maksimal 30 MB per file)`
  }
  return null
}

const createUploadBatches = (files: File[]): File[][] => {
  const batches: File[][] = []
  let currentBatch: File[] = []
  let currentBytes = 0

  files.forEach((file) => {
    const exceedsFileCount = currentBatch.length >= UPLOAD_BATCH_MAX_FILES
    const exceedsBatchSize = currentBatch.length > 0
      && currentBytes + file.size > UPLOAD_BATCH_MAX_BYTES

    if (exceedsFileCount || exceedsBatchSize) {
      batches.push(currentBatch)
      currentBatch = []
      currentBytes = 0
    }

    currentBatch.push(file)
    currentBytes += file.size
  })

  if (currentBatch.length) batches.push(currentBatch)
  return batches
}

const extractApiErrorMessage = (error: any, fallback: string) =>
  (typeof error === 'string' && error)
  || error?.data?.response?.message
  || error?.data?.metaData?.message
  || error?.response?.data?.response?.message
  || error?.response?.data?.metaData?.message
  || error?.message
  || fallback

const postUdsSilently = async (url: string, payload: any) => {
  try {
    const response = await api.postNoMessage(url, payload)
    const code = Number(response?.metaData?.code ?? 500)
    if (code < 200 || code >= 300) {
      throw new Error(response?.response?.message || response?.metaData?.message || 'Permintaan gagal')
    }
    return response?.response
  } catch (error: any) {
    throw new Error(extractApiErrorMessage(error, 'Permintaan gagal'))
  }
}

const uploadFilesDirect = async (jenis: JenisUdr, files: File[], folderUpload: boolean): Promise<boolean> => {
  if (!files.length) return true

  const state = sections[jenis]
  const batches = createUploadBatches(files)
  let uploadedCount = 0
  state.uploading = true
  state.uploadCompleted = 0
  state.uploadTotal = files.length
  state.uploadCurrentName = ''

  try {
    for (const batch of batches) {
      const formData = new FormData()
      formData.append('jenisudr', jenis)
      formData.append('parent_id', state.currentFolder === null ? '' : String(state.currentFolder))

      batch.forEach((file) => {
        formData.append('files[]', file)
        formData.append('relative_paths[]', relativePathFor(file, folderUpload))
      })

      state.uploadCurrentName = relativePathFor(batch[0], folderUpload)
      const response = await postUdsSilently('/udr/new-drive-upload', formData)
      uploadedCount += Number(response?.count ?? batch.length)
      state.uploadCompleted = Math.min(uploadedCount, files.length)
    }

    await loadSection(jenis)
    toast.success(`${uploadedCount} file dan struktur folder berhasil diupload`)
    return true
  } catch (error: any) {
    if (uploadedCount > 0) await loadSection(jenis)
    const progress = uploadedCount > 0 ? `${uploadedCount} dari ${files.length} file berhasil. ` : ''
    H.alert('error', `${progress}${extractApiErrorMessage(error, 'Upload folder gagal')}`)
    return false
  } finally {
    state.uploading = false
    state.uploadCompleted = 0
    state.uploadTotal = 0
    state.uploadCurrentName = ''
  }
}

const uploadFiles = async (jenis: JenisUdr, files: File[], folderUpload = false): Promise<void> => {
  if (!files.length || sections[jenis].trash || sections[jenis].uploading || automaticRevisionQueue.active) return

  const state = sections[jenis]
  const skippedFiles = files
    .map((file) => uploadValidationError(file))
    .filter((message): message is string => Boolean(message))
  const uploadableFiles = files.filter((file) => !uploadValidationError(file))

  if (skippedFiles.length) {
    const preview = skippedFiles.slice(0, 3).join(', ')
    const remainder = skippedFiles.length > 3 ? `, dan ${skippedFiles.length - 3} file lainnya` : ''
    const validFilesNotice = uploadableFiles.length ? ' File lain yang valid tetap diupload.' : ''
    H.alert(
      'warning',
      `${skippedFiles.length} file dilewati: ${preview}${remainder}.${validFilesNotice}`,
    )
  }

  if (!uploadableFiles.length) return

  const relativePaths = uploadableFiles.map((file) => relativePathFor(file, folderUpload))

  let matches: any[] = []
  state.uploading = true
  state.uploadCompleted = 0
  state.uploadTotal = uploadableFiles.length
  state.uploadCurrentName = 'Memeriksa isi folder…'

  try {
    for (let offset = 0; offset < uploadableFiles.length; offset += CONFLICT_CHECK_BATCH_SIZE) {
      const fileBatch = uploadableFiles.slice(offset, offset + CONFLICT_CHECK_BATCH_SIZE)
      const pathBatch = relativePaths.slice(offset, offset + CONFLICT_CHECK_BATCH_SIZE)
      const response = await postUdsSilently('/udr/new-drive-check-upload', {
        jenisudr: jenis,
        parent_id: state.currentFolder,
        files: fileBatch.map((file) => file.name),
        relative_paths: pathBatch,
      })

      const batchMatches = (response?.matches ?? []).map((match: any) => ({
        ...match,
        index: Number(match.index) + offset,
      }))
      matches.push(...batchMatches)
    }
  } catch (error: any) {
    H.alert('error', extractApiErrorMessage(error, 'Gagal memeriksa file sebelum upload'))
    return
  } finally {
    state.uploading = false
    state.uploadCompleted = 0
    state.uploadTotal = 0
    state.uploadCurrentName = ''
  }

  const matchesByIndex = new Map<number, any>(
    matches.map((match: any): [number, any] => [Number(match.index), match]),
  )
  const conflicts: Array<{ item: DriveItem; file: File }> = []
  const newFiles: File[] = []
  const deferredFiles: File[] = []
  const firstUploadKeys = new Set<string>()

  uploadableFiles.forEach((file, index) => {
    const match = matchesByIndex.get(index)
    if (match) {
      conflicts.push({
        file,
        item: {
          id: Number(match.id),
          root_id: Number(match.root_id),
          parent_id: match.parent_id === null ? null : Number(match.parent_id),
          name: String(match.name),
          kind: 'file',
          extension: String(match.extension || ''),
          description: match.description || '',
        },
      })
      return
    }

    const uploadKey = String(relativePaths[index] || file.name).toLocaleLowerCase('id-ID')
    if (firstUploadKeys.has(uploadKey)) {
      deferredFiles.push(file)
    } else {
      firstUploadKeys.add(uploadKey)
      newFiles.push(file)
    }
  })

  if (conflicts.length) {
    automaticRevisionQueue.active = true
    automaticRevisionQueue.jenis = jenis
    automaticRevisionQueue.folderUpload = folderUpload
    automaticRevisionQueue.items = conflicts
    automaticRevisionQueue.newFiles = newFiles
    automaticRevisionQueue.deferredFiles = deferredFiles
    automaticRevisionQueue.total = conflicts.length

    const first = automaticRevisionQueue.items[0]
    openReplaceFile(jenis, first.item, false, first.file, true)
    return
  }

  const uploaded = newFiles.length ? await uploadFilesDirect(jenis, newFiles, folderUpload) : true
  if (uploaded && deferredFiles.length) {
    await uploadFiles(jenis, deferredFiles, folderUpload)
  }
}

const handlePickedFiles = async (jenis: JenisUdr, event: Event, folderUpload: boolean) => {
  const input = event.target as HTMLInputElement
  const files = Array.from(input.files || [])
  input.value = ''
  await uploadFiles(jenis, files, folderUpload)
}

const onDragState = (jenis: JenisUdr, active: boolean) => {
  if (!canEdit(jenis) || sections[jenis].trash || sections[jenis].uploading) return
  sections[jenis].dragging = active
}

const readAllDirectoryEntries = async (reader: any): Promise<any[]> => {
  const entries: any[] = []

  while (true) {
    const batch = await new Promise<any[]>((resolve, reject) => {
      reader.readEntries(resolve, reject)
    })
    if (!batch.length) break
    entries.push(...batch)
  }

  return entries
}

const collectDroppedEntry = async (
  entry: any,
  parentPath: string,
  collectedFiles: File[],
): Promise<void> => {
  const currentPath = normalizeRelativePath(
    parentPath ? `${parentPath}/${entry.name}` : entry.name,
    entry.name,
  )

  if (entry.isFile) {
    const file = await new Promise<File>((resolve, reject) => entry.file(resolve, reject))
    droppedRelativePaths.set(file, currentPath)
    collectedFiles.push(file)
    return
  }

  if (!entry.isDirectory) return
  const children = await readAllDirectoryEntries(entry.createReader())
  for (const child of children) {
    await collectDroppedEntry(child, currentPath, collectedFiles)
  }
}

const collectDroppedFiles = async (dataTransfer: DataTransfer) => {
  const collectedFiles: File[] = []
  let folderUpload = false
  const items = Array.from(dataTransfer.items || []).filter((item) => item.kind === 'file')

  if (items.length) {
    for (const item of items) {
      const entry = (item as any).webkitGetAsEntry?.()
      if (entry) {
        folderUpload = folderUpload || Boolean(entry.isDirectory)
        await collectDroppedEntry(entry, '', collectedFiles)
        continue
      }

      const file = item.getAsFile()
      if (file) collectedFiles.push(file)
    }
  }

  if (!collectedFiles.length) {
    collectedFiles.push(...Array.from(dataTransfer.files || []))
  }

  return { files: collectedFiles, folderUpload }
}

const handleDrop = async (jenis: JenisUdr, event: DragEvent) => {
  const state = sections[jenis]
  state.dragging = false
  if (!canEdit(jenis) || state.trash || state.uploading || !event.dataTransfer) return

  state.uploading = true
  state.uploadCompleted = 0
  state.uploadTotal = 0
  state.uploadCurrentName = 'Membaca struktur folder…'

  let dropped: { files: File[]; folderUpload: boolean }
  try {
    dropped = await collectDroppedFiles(event.dataTransfer)
  } catch (error: any) {
    H.alert('error', `Folder tidak dapat dibaca: ${error?.message ?? 'akses folder ditolak browser'}`)
    return
  } finally {
    state.uploading = false
    state.uploadCompleted = 0
    state.uploadTotal = 0
    state.uploadCurrentName = ''
  }

  if (!dropped.files.length) {
    H.alert('warning', 'Folder tidak berisi file yang dapat diupload')
    return
  }

  await uploadFiles(jenis, dropped.files, dropped.folderUpload)
}

const openCreateFolder = (jenis: JenisUdr) => {
  folderDialog.jenis = jenis
  folderDialog.name = ''
  folderDialog.visible = true
}

const resetFolderDialog = () => {
  folderDialog.name = ''
  folderDialog.loading = false
}

const createFolder = async () => {
  const name = folderDialog.name.trim()
  if (!name) {
    H.alert('warning', 'Nama folder wajib diisi')
    return
  }

  folderDialog.loading = true
  try {
    await api.post('/udr/new-drive-create-folder', {
      jenisudr: folderDialog.jenis,
      parent_id: sections[folderDialog.jenis].currentFolder,
      name,
    })
    folderDialog.visible = false
    await loadSection(folderDialog.jenis)
  } catch (error: any) {
    H.alert('error', error?.message ?? 'Gagal membuat folder')
  } finally {
    folderDialog.loading = false
  }
}

const resetCvDialog = () => {
  cvDialog.loading = false
  cvDialog.saving = false
  cvDialog.parentId = null
  cvDialog.folderName = ''
  cvDialog.selected = null
  cvDialog.options = []
}

const openCvDialog = async (jenis: JenisUdr) => {
  if (jenis !== 'Mutu' || !canEdit(jenis)) return

  const state = sections[jenis]
  if (state.currentFolder === null) {
    H.alert('warning', 'Buka folder tujuan terlebih dahulu sebelum memasukkan CV')
    return
  }

  const currentCrumb = state.breadcrumbs[state.breadcrumbs.length - 1]
  cvDialog.parentId = state.currentFolder
  cvDialog.folderName = currentCrumb?.name ?? 'Folder Program Mutu'
  cvDialog.selected = null
  cvDialog.options = []
  cvDialog.loading = true
  cvDialog.visible = true

  try {
    const response = await api.get(
      'general/dropdown/pegawai_m?select=id,namalengkap&param_search=namalengkap&query=&limit=1000',
    )
    cvDialog.options = Array.isArray(response) ? response : []
  } catch (error: any) {
    H.alert('error', extractApiErrorMessage(error, 'Gagal memuat daftar pegawai'))
    cvDialog.visible = false
  } finally {
    cvDialog.loading = false
  }
}

const saveCvLink = async () => {
  if (cvDialog.parentId === null || !cvDialog.selected?.value) return

  cvDialog.saving = true
  try {
    await api.post('/udr/new-drive-cv', {
      parent_id: cvDialog.parentId,
      pegawai_id: cvDialog.selected.value,
    })
    cvDialog.visible = false
    await loadSection('Mutu')
  } catch (error: any) {
    H.alert('error', extractApiErrorMessage(error, 'Gagal memasukkan CV'))
  } finally {
    cvDialog.saving = false
  }
}

const resetFolderAccessDialog = () => {
  folderAccessDialog.loading = false
  folderAccessDialog.saving = false
  folderAccessDialog.item = null
  folderAccessDialog.mode = 'all'
  folderAccessDialog.selectedUserIds = []
  folderAccessDialog.users = []
  folderAccessDialog.search = ''
}

const openFolderAccess = async (jenis: JenisUdr, item: DriveItem) => {
  if (!canManageFolderAccess(item) || item.kind !== 'folder') return

  closeContextMenu()
  folderAccessDialog.jenis = jenis
  folderAccessDialog.item = item
  folderAccessDialog.mode = item.access_mode ?? 'all'
  folderAccessDialog.selectedUserIds = []
  folderAccessDialog.users = []
  folderAccessDialog.search = ''
  folderAccessDialog.loading = true
  folderAccessDialog.visible = true

  try {
    const response = await api.get(`/udr/new-drive-folder-access?id=${item.id}`)
    folderAccessDialog.mode = ['all', 'selected', 'excluded'].includes(response?.mode)
      ? response.mode
      : 'all'
    folderAccessDialog.selectedUserIds = (response?.selected_user_ids ?? []).map(Number)
    folderAccessDialog.users = (response?.users ?? []).map((account: any) => ({
      id: Number(account.id),
      username: String(account.username || ''),
      name: String(account.name || account.username || '-'),
      group: account.group ? String(account.group) : null,
    }))
  } catch (error: any) {
    H.alert('error', extractApiErrorMessage(error, 'Gagal memuat pengaturan akses folder'))
    folderAccessDialog.visible = false
  } finally {
    folderAccessDialog.loading = false
  }
}

const toggleFolderAccessUser = (userId: number) => {
  const index = folderAccessDialog.selectedUserIds.indexOf(userId)
  if (index >= 0) {
    folderAccessDialog.selectedUserIds.splice(index, 1)
  } else {
    folderAccessDialog.selectedUserIds.push(userId)
  }
}

const saveFolderAccess = async () => {
  if (!folderAccessDialog.item || folderAccessDialog.loading) return
  if (folderAccessDialog.mode !== 'all' && !folderAccessDialog.selectedUserIds.length) {
    H.alert(
      'warning',
      folderAccessDialog.mode === 'selected'
        ? 'Pilih minimal satu akun yang boleh membuka folder'
        : 'Pilih minimal satu akun yang dikecualikan dari folder',
    )
    return
  }

  const item = folderAccessDialog.item
  const jenis = folderAccessDialog.jenis
  folderAccessDialog.saving = true

  try {
    await api.post('/udr/new-drive-folder-access', {
      id: item.id,
      mode: folderAccessDialog.mode,
      user_ids: folderAccessDialog.mode === 'all'
        ? []
        : folderAccessDialog.selectedUserIds,
    })
    folderAccessDialog.visible = false
    await loadSection(jenis)
  } catch (error: any) {
    H.alert('error', extractApiErrorMessage(error, 'Gagal menyimpan akses folder'))
  } finally {
    folderAccessDialog.saving = false
  }
}

const openContextMenu = (event: MouseEvent, jenis: JenisUdr, item: DriveItem) => {
  positionContextMenu(event.clientX, event.clientY, jenis, item)
}

const positionContextMenu = (x: number, y: number, jenis: JenisUdr, item: DriveItem) => {
  sections[jenis].selected = item
  contextMenu.jenis = jenis
  contextMenu.item = item
  contextMenu.x = Math.max(12, Math.min(x, window.innerWidth - 238))
  contextMenu.y = Math.max(12, Math.min(y, window.innerHeight - 380))
  contextMenu.visible = true
}

const openMoreMenu = (event: MouseEvent, jenis: JenisUdr, item: DriveItem) => {
  const target = event.currentTarget as HTMLElement
  const rect = target.getBoundingClientRect()
  positionContextMenu(rect.right, rect.bottom, jenis, item)
}

function closeContextMenu() {
  contextMenu.visible = false
}

const openRename = (jenis: JenisUdr, item: DriveItem) => {
  if (item.is_attachment) return
  closeContextMenu()
  renameDialog.jenis = jenis
  renameDialog.item = item
  renameDialog.name = item.name
  renameDialog.visible = true
}

const resetRenameDialog = () => {
  renameDialog.item = null
  renameDialog.name = ''
  renameDialog.loading = false
}

const renameItem = async () => {
  if (!renameDialog.item || !renameDialog.name.trim()) return
  renameDialog.loading = true

  try {
    await api.post('/udr/new-drive-rename', {
      id: renameDialog.item.id,
      name: renameDialog.name.trim(),
    })
    renameDialog.visible = false
    await loadSection(renameDialog.jenis)
  } catch (error: any) {
    H.alert('error', error?.message ?? 'Gagal mengubah nama')
  } finally {
    renameDialog.loading = false
  }
}

const openMove = async (jenis: JenisUdr, item: DriveItem) => {
  if (item.is_attachment) return
  closeContextMenu()
  moveDialog.jenis = jenis
  moveDialog.item = item
  moveDialog.destination = null
  moveDialog.loading = true
  moveDialog.visible = true

  try {
    const response = await api.get('/udr/new-drive-folders?jenisudr=' + jenis)
    const folders = (response?.folders ?? []).filter((folder: any) => Number(folder.root_id) !== Number(item.root_id))
    moveDialog.options = [
      { value: null, label: `Program ${jenis} (utama)` },
      ...folders.map((folder: any) => ({
        value: folder.id,
        label: folder.path || folder.name,
      })),
    ]
    moveDialog.destination = moveDialog.options.find(
      (option: any) => Number(option.value ?? 0) === Number(item.parent_id ?? 0),
    ) ?? moveDialog.options[0]
  } catch (error: any) {
    moveDialog.options = [{ value: null, label: `Program ${jenis} (utama)` }]
    moveDialog.destination = moveDialog.options[0]
    H.alert('error', error?.message ?? 'Gagal memuat folder tujuan')
  } finally {
    moveDialog.loading = false
  }
}

const resetMoveDialog = () => {
  moveDialog.item = null
  moveDialog.destination = null
  moveDialog.options = []
  moveDialog.loading = false
}

const moveItem = async () => {
  if (!moveDialog.item || !moveDialog.destination) return
  moveDialog.loading = true

  try {
    await api.post('/udr/new-drive-move', {
      id: moveDialog.item.id,
      parent_id: moveDialog.destination.value,
    })
    moveDialog.visible = false
    await loadSection(moveDialog.jenis)
  } catch (error: any) {
    H.alert('error', error?.message ?? 'Gagal memindahkan item')
  } finally {
    moveDialog.loading = false
  }
}

const trashItem = (jenis: JenisUdr, item: DriveItem) => {
  if (item.is_attachment) return
  closeContextMenu()
  confirmDialog.require({
    group: 'uds-drive-confirm',
    header: 'Konfirmasi Pindahkan ke Sampah',
    message: `Pindahkan “${item.name}” ke Sampah?${item.kind === 'folder' ? ' Seluruh isi folder ikut dipindahkan.' : ''}`,
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Ya, Pindahkan',
    rejectLabel: 'Batal',
    acceptClass: 'p-button-danger',
    rejectClass: 'p-button-secondary p-button-outlined',
    accept: async () => {
      try {
        await api.post('/udr/new-drive-trash', { id: item.id })
        toast.success(`“${item.name}” berhasil dipindahkan ke Sampah`)
        await loadSection(jenis)
      } catch (error: any) {
        H.alert('error', error?.message ?? 'Gagal memindahkan ke Sampah')
      }
    },
  })
}

const restoreItem = async (jenis: JenisUdr, item: DriveItem) => {
  closeContextMenu()
  try {
    await api.post('/udr/new-drive-restore', { id: item.id })
    await loadSection(jenis)
  } catch (error: any) {
    H.alert('error', error?.message ?? 'Gagal memulihkan item')
  }
}

const resetLinkDialog = () => {
  linkDialog.loading = false
  linkDialog.saving = false
  linkDialog.item = null
  linkDialog.selected = null
  linkDialog.options = []
  linkDialog.currentId = null
  linkDialog.currentLabel = ''
  linkDialog.targetType = 'file'
}

const openLinkDialog = async (jenis: JenisUdr, item: DriveItem) => {
  if (!canEdit(jenis) || !['file', 'folder'].includes(item.kind)) return

  closeContextMenu()
  linkDialog.jenis = jenis
  linkDialog.item = item
  linkDialog.targetType = item.kind === 'folder' ? 'folder' : 'file'
  linkDialog.selected = null
  linkDialog.currentId = null
  linkDialog.currentLabel = ''
  linkDialog.loading = true
  linkDialog.visible = true

  try {
    const [lookup, info] = await Promise.all([
      api.get('/udr/lookup-nomor-induk'),
      api.get('/udr/new-drive-link-info?id=' + item.id),
    ])

    linkDialog.options = lookup?.data ?? []
    linkDialog.currentId = info?.nomorindukfk ? Number(info.nomorindukfk) : null
    linkDialog.currentLabel = info?.nomor_dokumen
      ? `${info.nomor_dokumen}${info.nama_dokumen ? ` - ${info.nama_dokumen}` : ''}`
      : ''
    linkDialog.selected = linkDialog.options.find(
      (option: any) => Number(option.key) === Number(linkDialog.currentId),
    ) ?? null
  } catch (error: any) {
    H.alert('error', error?.response?.data?.message ?? error?.message ?? 'Gagal memuat daftar nomor induk')
    linkDialog.visible = false
  } finally {
    linkDialog.loading = false
  }
}

const saveNomorIndukLink = async () => {
  if (!linkDialog.item || !linkDialog.selected) return
  linkDialog.saving = true

  try {
    await api.post('/udr/new-drive-link-nomor-induk', {
      id: linkDialog.item.id,
      nomorindukfk: linkDialog.selected.key,
      target_type: linkDialog.targetType,
    })
    toast.success(
      `${linkDialog.targetType === 'folder' ? 'Folder' : 'File'} berhasil dihubungkan ke Daftar Induk Dokumen`,
    )
    linkDialog.visible = false
    await loadSection(linkDialog.jenis)
  } catch (error: any) {
    H.alert('error', error?.response?.data?.message ?? error?.message ?? 'Gagal menghubungkan dokumen')
  } finally {
    linkDialog.saving = false
  }
}

const unlinkNomorInduk = () => {
  if (!linkDialog.item || !linkDialog.currentId) return
  const targetName = linkDialog.item.name

  confirmDialog.require({
    group: 'uds-drive-confirm',
    header: 'Konfirmasi Lepas Hubungan',
    message: `Lepas hubungan Daftar Induk Dokumen dari “${targetName}”?`,
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Ya, Lepaskan',
    rejectLabel: 'Batal',
    acceptClass: 'p-button-danger',
    rejectClass: 'p-button-secondary p-button-outlined',
    accept: async () => {
      if (!linkDialog.item) return
      linkDialog.saving = true

      try {
        await api.post('/udr/new-drive-link-nomor-induk', {
          id: linkDialog.item.id,
          nomorindukfk: null,
          target_type: linkDialog.targetType,
        })
        toast.success('Hubungan Daftar Induk Dokumen berhasil dilepas')
        linkDialog.visible = false
        await loadSection(linkDialog.jenis)
      } catch (error: any) {
        H.alert('error', error?.response?.data?.message ?? error?.message ?? 'Gagal melepas hubungan dokumen')
      } finally {
        linkDialog.saving = false
      }
    },
  })
}

const showActivity = async (jenis: JenisUdr, item: DriveItem) => {
  closeContextMenu()
  activityDialog.jenis = jenis
  activityDialog.item = item
  activityDialog.timeline = []
  activityDialog.loading = true
  activityDialog.visible = true

  try {
    const response = await api.get('/udr/new-drive-activity?id=' + item.id)
    activityDialog.timeline = response?.timeline ?? []
  } catch (error: any) {
    H.alert('error', error?.message ?? 'Gagal memuat aktivitas')
  } finally {
    activityDialog.loading = false
  }
}

const openActivityRevision = (activity: any) => {
  if (!activity?.can_open || !activity?.revision_id) return
  H.printBlade(`udr/new-drive-open-revision?id=${activity.revision_id}`)
}

const downloadActivityRevision = (activity: any) => {
  if (!activity?.can_download || !activity?.revision_id) return
  H.printBlade(`udr/new-drive-download-revision?id=${activity.revision_id}`)
}

const activityLabel = (action: string) => {
  const labels: Record<string, string> = {
    CREATE: 'Item dibuat',
    CREATE_FOLDER: 'Folder dibuat',
    UPLOAD: 'File diupload',
    UPLOAD_REVISION: 'Versi baru diupload',
    REVISION: 'Dokumen direvisi',
    RENAME: 'Nama diubah',
    MOVE: 'Item dipindahkan',
    TRASH: 'Dipindahkan ke Sampah',
    RESTORE: 'Item dipulihkan',
    LINK_NOMOR_INDUK: 'Terhubung ke Daftar Induk Dokumen',
    UNLINK_NOMOR_INDUK: 'Hubungan Daftar Induk Dokumen dilepas',
    LINK_SURVEILAN: 'Terhubung ke Daftar Induk Dokumen',
    UNLINK_SURVEILAN: 'Hubungan Daftar Induk Dokumen dilepas',
    ACCESS_UPDATE: 'Akses folder diubah',
    ADD_CV: 'CV dimasukkan',
  }
  return labels[action] || action
}

const activityIcon = (action: string) => {
  if (action === 'TRASH') return 'material-symbols:delete-outline-rounded'
  if (action === 'RESTORE') return 'material-symbols:restore-rounded'
  if (action === 'MOVE') return 'material-symbols:drive-file-move-outline-rounded'
  if (action === 'RENAME') return 'material-symbols:edit-outline-rounded'
  if (action === 'ACCESS_UPDATE') return 'material-symbols:manage-accounts-outline-rounded'
  if (['LINK_NOMOR_INDUK', 'UNLINK_NOMOR_INDUK', 'LINK_SURVEILAN', 'UNLINK_SURVEILAN'].includes(action)) {
    return 'material-symbols:link-rounded'
  }
  if (action.includes('UPLOAD') || action === 'REVISION') return 'material-symbols:upload-file-outline-rounded'
  if (action === 'CREATE_FOLDER') return 'material-symbols:create-new-folder-outline-rounded'
  return 'material-symbols:add-circle-outline-rounded'
}

const emptyTitle = (jenis: JenisUdr) => {
  if (sections[jenis].trash) return 'Sampah kosong'
  if (sections[jenis].search) return 'Dokumen tidak ditemukan'
  return 'Folder ini masih kosong'
}

const emptyDescription = (jenis: JenisUdr) => {
  if (sections[jenis].trash) return 'Item yang dihapus dari program ini akan muncul di sini.'
  if (sections[jenis].search) return 'Coba gunakan kata kunci lain atau bersihkan pencarian.'
  return canEdit(jenis) ? 'Upload file, upload folder, atau buat folder baru untuk mulai.' : 'Belum ada dokumen di lokasi ini.'
}

const queryValue = (value: unknown) => Array.isArray(value) ? value[0] : value

const openLinkedTargetFromQuery = async () => {
  const rawId = queryValue(route.query.openUdrId)
  if (!rawId || !/^\d+$/.test(String(rawId))) return

  const targetType = String(queryValue(route.query.targetType) ?? queryValue(route.query.target) ?? 'file').toLowerCase()
  if (targetType !== 'folder') {
    H.printBlade(`udr/new-drive-open?id=${rawId}`)
    return
  }

  const requestedJenis = String(queryValue(route.query.jenisudr) ?? '').toLowerCase()
  const jenis = jenisList.find((value) => value.toLowerCase() === requestedJenis)
  if (!jenis) {
    H.alert('warning', 'Program UDS untuk folder terkait tidak ditemukan')
    return
  }

  const state = sections[jenis]
  state.trash = false
  state.search = ''
  state.currentFolder = Number(rawId)
  await loadSection(jenis)
  await nextTick()
  document.querySelector(`[data-program="${jenis.toLowerCase()}"]`)?.scrollIntoView({
    behavior: 'smooth',
    block: 'start',
  })
}

onMounted(async () => {
  document.addEventListener('click', closeContextMenu)
  window.addEventListener('resize', closeContextMenu)
  if (props.auditInternal) {
    try {
      await api.post('/udr/new-drive-sync-audit-internal', {})
    } catch (error: any) {
      H.alert('error', error?.message ?? 'Struktur Dokumen Audit Internal gagal disiapkan')
    }
  }
  await reloadAll()
  await openLinkedTargetFromQuery()
})

onBeforeUnmount(() => {
  document.removeEventListener('click', closeContextMenu)
  window.removeEventListener('resize', closeContextMenu)
})
</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';

.uds-drive-new {
  --drive-bg: #f7f8fa;
  --drive-surface: #fff;
  --drive-surface-soft: #f8fafd;
  --drive-border: #dfe3e8;
  --drive-border-soft: #e9edf2;
  --drive-text: #202124;
  --drive-muted: #5f6368;
  --drive-blue: #0b57d0;
  --drive-blue-soft: #e8f0fe;
  --drive-hover: #f1f4f8;
  --drive-selected: #c2e7ff;
  --drive-danger: #b3261e;

  width: 100%;
  color: var(--drive-text);
  font-family: inherit;
}

.drive-page-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  margin-bottom: 18px;
  padding: 18px 20px;
  border: 1px solid var(--drive-border);
  border-radius: 18px;
  background: var(--drive-surface);
  box-shadow: 0 1px 2px rgba(32, 33, 36, .06);
}

.drive-eyebrow {
  margin-bottom: 5px;
  color: var(--drive-blue);
  font-size: .72rem;
  font-weight: 700;
  letter-spacing: .11em;
}

.drive-page-head h1 {
  margin: 0;
  color: var(--drive-text);
  font-size: 1.65rem;
  font-weight: 650;
  letter-spacing: -.02em;
}

.drive-page-head p {
  margin: 5px 0 0;
  color: var(--drive-muted);
  font-size: .92rem;
}

.drive-head-badge {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 230px;
  padding: 10px 13px;
  border-radius: 14px;
  background: var(--drive-blue-soft);
  color: var(--drive-blue);
}

.drive-head-badge > .iconify {
  width: 25px;
  height: 25px;
}

.drive-head-badge div {
  display: flex;
  flex-direction: column;
}

.drive-head-badge strong {
  font-size: .84rem;
  font-weight: 650;
}

.drive-head-badge span {
  margin-top: 1px;
  color: var(--drive-muted);
  font-size: .72rem;
}

.drive-space {
  margin-bottom: 22px;
  overflow: hidden;
  border: 1px solid var(--drive-border);
  border-radius: 20px;
  background: var(--drive-surface);
  box-shadow: 0 1px 2px rgba(32, 33, 36, .06);
}

.drive-space-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 17px 20px 13px;
}

.drive-program-title,
.drive-head-actions,
.drive-create-actions,
.drive-view-actions {
  display: flex;
  align-items: center;
  gap: 9px;
}

.drive-program-mark {
  display: grid;
  width: 43px;
  height: 43px;
  place-items: center;
  border-radius: 13px;
  color: #fff;
  background: #0b57d0;
}

.drive-program-mark.is-teknik {
  background: #188038;
}

.drive-program-mark.is-admin {
  background: #a142f4;
}

.drive-program-mark .iconify {
  width: 23px;
  height: 23px;
}

.drive-program-title h2 {
  margin: 0;
  color: var(--drive-text);
  font-size: 1.12rem;
  font-weight: 650;
}

.drive-program-title p {
  margin: 3px 0 0;
  color: var(--drive-muted);
  font-size: .78rem;
}

.drive-program-title p span {
  padding: 0 4px;
}

.drive-toolbar {
  display: grid;
  grid-template-columns: minmax(260px, auto) minmax(260px, 1fr) auto;
  align-items: center;
  gap: 14px;
  padding: 10px 20px;
  border-top: 1px solid var(--drive-border-soft);
  border-bottom: 1px solid var(--drive-border-soft);
  background: var(--drive-surface-soft);
}

.drive-action-button,
.drive-trash-button,
.drive-icon-button,
.drive-more-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 0;
  color: var(--drive-muted);
  background: transparent;
  cursor: pointer;
  transition: background .15s ease, color .15s ease;
}

.drive-action-button {
  gap: 7px;
  min-height: 36px;
  padding: 0 11px;
  border: 1px solid var(--drive-border);
  border-radius: 10px;
  color: var(--drive-text);
  background: var(--drive-surface);
  font-size: .8rem;
  font-weight: 600;
}

.drive-action-button:hover,
.drive-trash-button:hover,
.drive-icon-button:hover,
.drive-more-button:hover {
  background: var(--drive-hover);
}

.drive-action-button:disabled {
  cursor: wait;
  opacity: .55;
}

.drive-action-button .iconify,
.drive-trash-button .iconify {
  width: 18px;
  height: 18px;
}

.drive-search {
  display: flex;
  align-items: center;
  gap: 8px;
  min-height: 40px;
  padding: 0 12px;
  border: 1px solid transparent;
  border-radius: 24px;
  background: #edf2f7;
}

.drive-search:focus-within {
  border-color: #a8c7fa;
  background: var(--drive-surface);
  box-shadow: 0 1px 3px rgba(60, 64, 67, .18);
}

.drive-search > .iconify {
  width: 20px;
  height: 20px;
  color: var(--drive-muted);
}

.drive-search input {
  width: 100%;
  border: 0;
  outline: 0;
  color: var(--drive-text);
  background: transparent;
  font: inherit;
  font-size: .87rem;
}

.drive-search button {
  display: grid;
  width: 27px;
  height: 27px;
  flex: 0 0 27px;
  place-items: center;
  border: 0;
  border-radius: 50%;
  color: var(--drive-muted);
  background: transparent;
  cursor: pointer;
}

.drive-icon-button {
  width: 36px;
  height: 36px;
  border-radius: 50%;
}

.drive-icon-button .iconify,
.drive-more-button .iconify {
  width: 20px;
  height: 20px;
}

.drive-icon-button.is-active {
  color: var(--drive-blue);
  background: var(--drive-blue-soft);
}

.drive-trash-button {
  gap: 6px;
  min-height: 36px;
  padding: 0 10px;
  border-radius: 9px;
  font-size: .8rem;
  font-weight: 600;
}

.drive-trash-button.is-active {
  color: var(--drive-danger);
  background: rgba(179, 38, 30, .09);
}

.drive-breadcrumbs,
.drive-trash-note {
  display: flex;
  align-items: center;
  min-height: 45px;
  gap: 2px;
  padding: 6px 20px;
  border-bottom: 1px solid var(--drive-border-soft);
}

.drive-breadcrumbs button {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-height: 31px;
  padding: 0 8px;
  border: 0;
  border-radius: 7px;
  color: var(--drive-text);
  background: transparent;
  font: inherit;
  font-size: .82rem;
  font-weight: 550;
  cursor: pointer;
}

.drive-breadcrumbs button:hover {
  background: var(--drive-hover);
}

.drive-breadcrumb-separator {
  width: 17px;
  height: 17px;
  color: var(--drive-muted);
}

.drive-search-caption {
  margin-left: auto;
  color: var(--drive-muted);
  font-size: .77rem;
}

.drive-trash-note {
  gap: 8px;
  color: var(--drive-muted);
  font-size: .82rem;
  background: rgba(179, 38, 30, .035);
}

.drive-trash-note .iconify {
  width: 19px;
  height: 19px;
  color: var(--drive-danger);
}

.drive-content {
  position: relative;
  min-height: 300px;
  padding: 16px 20px 20px;
  background: var(--drive-surface);
}

.drive-content.is-dragover {
  outline: 2px solid #0b57d0;
  outline-offset: -5px;
  background: #f4f8ff;
}

.drive-loading,
.drive-empty {
  display: flex;
  min-height: 265px;
  align-items: center;
  justify-content: center;
  color: var(--drive-muted);
}

.drive-loading {
  flex-direction: row;
  gap: 12px;
  font-size: .86rem;
}

.drive-empty {
  flex-direction: column;
  text-align: center;
}

.drive-empty-icon {
  display: grid;
  width: 66px;
  height: 66px;
  margin-bottom: 12px;
  place-items: center;
  border-radius: 50%;
  color: #7a828a;
  background: var(--drive-hover);
}

.drive-empty-icon .iconify {
  width: 33px;
  height: 33px;
}

.drive-empty strong {
  color: var(--drive-text);
  font-size: .95rem;
  font-weight: 650;
}

.drive-empty p {
  max-width: 390px;
  margin: 5px 0 0;
  font-size: .82rem;
}

.drive-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
  gap: 12px;
}

.drive-card {
  min-width: 0;
  padding: 12px;
  border: 1px solid var(--drive-border-soft);
  border-radius: 13px;
  outline: 0;
  background: var(--drive-surface-soft);
  cursor: default;
  user-select: none;
  transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
}

.drive-card:hover {
  border-color: #bdc5cf;
  box-shadow: 0 1px 3px rgba(60, 64, 67, .13);
}

.drive-card:focus-visible {
  box-shadow: 0 0 0 2px #a8c7fa;
}

.drive-card.is-selected {
  border-color: #7db7e8;
  background: var(--drive-selected);
}

.drive-card.is-locked {
  border-color: #d4d8de;
  background: #f2f3f5;
}

.drive-card.is-locked .drive-card-name,
.drive-list-table tr.is-locked .drive-list-name {
  color: var(--drive-muted);
}

.drive-card-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  min-height: 52px;
}

.drive-lock-badge {
  display: grid;
  width: 27px;
  height: 27px;
  margin-left: auto;
  place-items: center;
  border-radius: 50%;
  color: #7a4f01;
  background: #fff0c2;
}

.drive-lock-badge .iconify {
  width: 16px;
  height: 16px;
}

.drive-card.is-locked .drive-more-button {
  margin-left: 5px;
}

.drive-file-icon {
  display: inline-grid;
  width: 45px;
  height: 45px;
  flex: 0 0 45px;
  place-items: center;
  border-radius: 10px;
  color: #5f6368;
  background: #eef1f4;
}

.drive-file-icon .iconify {
  width: 31px;
  height: 31px;
}

.drive-file-icon.is-folder {
  background: #fff7db;
}

.drive-file-icon.is-link {
  color: #0b57d0;
  background: #e8f0fe;
}

.drive-file-icon.is-word {
  background: #eaf2ff;
}

.drive-file-icon.is-excel {
  background: #e8f5ed;
}

.drive-file-icon.is-pdf {
  background: #fcebea;
}

.drive-file-icon.is-image {
  background: #f3edff;
}

.drive-file-icon.is-small {
  width: 34px;
  height: 34px;
  flex-basis: 34px;
  border-radius: 8px;
}

.drive-file-icon.is-small .iconify {
  width: 24px;
  height: 24px;
}

.drive-more-button {
  width: 31px;
  height: 31px;
  border-radius: 50%;
}

.drive-card-name {
  margin-top: 10px;
  overflow: hidden;
  color: var(--drive-text);
  font-size: .85rem;
  font-weight: 600;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.drive-card-meta {
  display: flex;
  justify-content: space-between;
  gap: 8px;
  margin-top: 7px;
  color: var(--drive-muted);
  font-size: .71rem;
}

.drive-card-date {
  margin-top: 4px;
  overflow: hidden;
  color: var(--drive-muted);
  font-size: .7rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.drive-list-wrap {
  overflow-x: auto;
}

.drive-list-table {
  width: 100%;
  border-collapse: collapse;
  color: var(--drive-text);
  font-size: .8rem;
}

.drive-list-table th {
  padding: 9px 12px;
  border-bottom: 1px solid var(--drive-border);
  color: var(--drive-muted);
  font-size: .72rem;
  font-weight: 600;
  text-align: left;
}

.drive-list-table td {
  padding: 8px 12px;
  border-bottom: 1px solid var(--drive-border-soft);
  color: var(--drive-muted);
  vertical-align: middle;
}

.drive-list-table tbody tr:hover,
.drive-list-table tbody tr.is-selected {
  background: var(--drive-hover);
}

.drive-list-table tbody tr.is-selected {
  background: var(--drive-selected);
}

.drive-list-name {
  display: flex;
  min-width: 240px;
  align-items: center;
  gap: 10px;
  color: var(--drive-text);
  font-weight: 550;
}

.drive-list-lock {
  width: 17px;
  height: 17px;
  flex: 0 0 17px;
  color: #9a6700;
}

.drive-list-name > span:last-child {
  max-width: 420px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.drive-drop-overlay {
  position: absolute;
  inset: 12px;
  z-index: 4;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  border: 2px dashed #0b57d0;
  border-radius: 15px;
  color: var(--drive-blue);
  background: rgba(232, 240, 254, .94);
  pointer-events: none;
}

.drive-drop-overlay .iconify {
  width: 46px;
  height: 46px;
  margin-bottom: 8px;
}

.drive-drop-overlay strong {
  font-size: 1rem;
}

.drive-drop-overlay span {
  margin-top: 3px;
  color: var(--drive-muted);
  font-size: .8rem;
}

.drive-space-foot {
  display: flex;
  min-height: 39px;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 7px 20px;
  border-top: 1px solid var(--drive-border-soft);
  color: var(--drive-muted);
  background: var(--drive-surface-soft);
  font-size: .72rem;
}

.drive-space-foot span {
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.drive-context-menu {
  position: fixed;
  z-index: 10000;
  width: 225px;
  max-height: calc(100vh - 24px);
  padding: 6px;
  overflow-y: auto;
  border: 1px solid var(--drive-border, #dfe3e8);
  border-radius: 12px;
  background: var(--drive-surface, #fff);
  box-shadow: 0 8px 26px rgba(60, 64, 67, .24);
}

.drive-context-menu button {
  display: flex;
  width: 100%;
  min-height: 37px;
  align-items: center;
  gap: 10px;
  padding: 0 10px;
  border: 0;
  border-radius: 7px;
  color: var(--drive-text, #202124);
  background: transparent;
  font: inherit;
  font-size: .82rem;
  text-align: left;
  cursor: pointer;
}

.drive-context-menu button:hover {
  background: var(--drive-hover, #f1f4f8);
}

.drive-context-menu button:disabled {
  color: var(--drive-muted, #5f6368);
  cursor: not-allowed;
  opacity: .7;
}

.drive-context-menu button:disabled:hover {
  background: transparent;
}

.drive-context-menu button.is-danger {
  color: var(--drive-danger, #b3261e);
}

.drive-context-menu button .iconify {
  width: 19px;
  height: 19px;
}

.drive-context-separator {
  height: 1px;
  margin: 5px 6px;
  background: var(--drive-border-soft, #e9edf2);
}

.drive-dialog-caption {
  margin-bottom: 16px;
  color: var(--light-text);
  font-size: .86rem;
}

.drive-access-dialog {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.drive-access-target {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 13px 14px;
  border: 1px solid var(--fade-grey, #e5e7eb);
  border-radius: 12px;
  background: var(--widget-grey, #f8fafc);
}

.drive-access-target > div {
  display: flex;
  min-width: 0;
  flex: 1;
  flex-direction: column;
}

.drive-access-target span,
.drive-access-target small {
  color: var(--light-text);
  font-size: .73rem;
}

.drive-access-target strong {
  overflow: hidden;
  margin: 1px 0;
  color: var(--dark-text);
  font-size: .9rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.drive-access-loading {
  display: flex;
  min-height: 180px;
  align-items: center;
  justify-content: center;
  gap: 10px;
  color: var(--light-text);
  font-size: .84rem;
}

.drive-access-modes {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 9px;
}

.drive-access-mode {
  display: flex;
  min-width: 0;
  align-items: flex-start;
  gap: 9px;
  padding: 12px;
  border: 1px solid var(--fade-grey, #e5e7eb);
  border-radius: 11px;
  cursor: pointer;
  transition: border-color .15s ease, background .15s ease;
}

.drive-access-mode.is-selected {
  border-color: #6ea8e5;
  background: #eef5ff;
}

.drive-access-mode > input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.drive-access-mode-icon {
  display: grid;
  width: 30px;
  height: 30px;
  flex: 0 0 30px;
  place-items: center;
  border-radius: 8px;
  color: #0b57d0;
  background: #e8f0fe;
}

.drive-access-mode-icon .iconify {
  width: 18px;
  height: 18px;
}

.drive-access-mode > span:last-child {
  display: flex;
  min-width: 0;
  flex-direction: column;
}

.drive-access-mode strong {
  color: var(--dark-text);
  font-size: .78rem;
}

.drive-access-mode small {
  margin-top: 3px;
  color: var(--light-text);
  font-size: .67rem;
  line-height: 1.35;
}

.drive-access-users {
  overflow: hidden;
  border: 1px solid var(--fade-grey, #e5e7eb);
  border-radius: 12px;
}

.drive-access-users-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 11px 13px;
  border-bottom: 1px solid var(--fade-grey, #e5e7eb);
  background: var(--widget-grey, #f8fafc);
}

.drive-access-users-head > div {
  display: flex;
  flex-direction: column;
}

.drive-access-users-head strong {
  color: var(--dark-text);
  font-size: .8rem;
}

.drive-access-users-head span {
  color: var(--light-text);
  font-size: .68rem;
}

.drive-access-search {
  display: flex;
  width: min(260px, 55%);
  min-height: 34px;
  align-items: center;
  gap: 6px;
  padding: 0 9px;
  border: 1px solid var(--fade-grey, #dfe3e8);
  border-radius: 18px;
  background: var(--white, #fff);
}

.drive-access-search .iconify {
  width: 17px;
  height: 17px;
  color: var(--light-text);
}

.drive-access-search input {
  width: 100%;
  border: 0;
  outline: 0;
  color: var(--dark-text);
  background: transparent;
  font: inherit;
  font-size: .75rem;
}

.drive-access-user-list {
  max-height: 260px;
  overflow-y: auto;
}

.drive-access-user {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 13px;
  border-bottom: 1px solid var(--fade-grey, #eef0f3);
  cursor: pointer;
}

.drive-access-user:last-child {
  border-bottom: 0;
}

.drive-access-user:hover {
  background: var(--widget-grey, #f8fafc);
}

.drive-access-user input {
  width: 16px;
  height: 16px;
  accent-color: #0b57d0;
}

.drive-access-avatar {
  display: grid;
  width: 32px;
  height: 32px;
  flex: 0 0 32px;
  place-items: center;
  border-radius: 50%;
  color: #174ea6;
  background: #dbe9ff;
  font-size: .68rem;
  font-weight: 700;
}

.drive-access-user-main {
  display: flex;
  min-width: 0;
  flex: 1;
  flex-direction: column;
}

.drive-access-user-main strong {
  overflow: hidden;
  color: var(--dark-text);
  font-size: .78rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.drive-access-user-main small {
  color: var(--light-text);
  font-size: .68rem;
}

.drive-access-no-user {
  padding: 28px 14px;
  color: var(--light-text);
  font-size: .78rem;
  text-align: center;
}

.drive-access-note {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 9px 11px;
  border-radius: 9px;
  color: #5f4b00;
  background: #fff8df;
  font-size: .72rem;
}

.drive-access-note .iconify {
  width: 18px;
  height: 18px;
  flex: 0 0 18px;
}

.drive-revision-upload {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.drive-revision-target {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 13px;
  border: 1px solid var(--fade-grey, #e9edf2);
  border-radius: 12px;
  background: var(--widget-grey, #f7f8fa);
}

.drive-revision-target > div {
  display: flex;
  min-width: 0;
  flex: 1;
  flex-direction: column;
}

.drive-revision-target > div > span,
.drive-revision-target small {
  color: var(--light-text);
  font-size: .72rem;
}

.drive-revision-target .drive-revision-progress {
  width: fit-content;
  margin-top: 5px;
  padding: 3px 7px;
  border-radius: 99px;
  color: #0b57d0;
  background: #e8f0fe;
  font-weight: 650;
}

.drive-revision-target strong {
  overflow: hidden;
  margin: 2px 0;
  color: var(--dark-text);
  font-size: .88rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.drive-revision-picker {
  display: flex;
  min-height: 48px;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border: 1px dashed #0b57d0;
  border-radius: 12px;
  color: #0b57d0;
  background: #f6f9fe;
  cursor: pointer;
  font-size: .82rem;
  font-weight: 650;
}

.drive-revision-picker:hover {
  background: #e8f0fe;
}

.drive-revision-picker input {
  position: absolute;
  width: 1px;
  height: 1px;
  opacity: 0;
  pointer-events: none;
}

.drive-revision-picker .iconify {
  width: 20px;
  height: 20px;
}

.drive-revision-selected {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border: 1px solid var(--fade-grey, #e9edf2);
  border-radius: 10px;
}

.drive-revision-selected > .iconify {
  width: 22px;
  height: 22px;
  color: #0b57d0;
}

.drive-revision-selected > div {
  display: flex;
  min-width: 0;
  flex: 1;
  flex-direction: column;
}

.drive-revision-selected strong {
  overflow: hidden;
  color: var(--dark-text);
  font-size: .8rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.drive-revision-selected span {
  color: var(--light-text);
  font-size: .7rem;
}

.drive-revision-selected button {
  display: grid;
  width: 30px;
  height: 30px;
  place-items: center;
  border: 0;
  border-radius: 50%;
  color: var(--light-text);
  background: transparent;
  cursor: pointer;
}

.drive-revision-selected button:hover {
  color: var(--dark-text);
  background: var(--fade-grey, #e9edf2);
}

.drive-link-dialog,
.drive-cv-dialog {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.drive-link-target,
.drive-cv-target,
.drive-link-current {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 13px 14px;
  border: 1px solid var(--fade-grey, #e5e7eb);
  border-radius: 12px;
  background: var(--widget-grey, #f8fafc);
}

.drive-link-target > div,
.drive-cv-target > div,
.drive-link-current > div {
  display: flex;
  min-width: 0;
  flex-direction: column;
}

.drive-link-target span,
.drive-cv-target span,
.drive-link-current span {
  color: var(--light-text);
  font-size: .73rem;
}

.drive-link-target strong,
.drive-cv-target strong,
.drive-link-current strong {
  overflow: hidden;
  color: var(--dark-text);
  font-size: .9rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.drive-link-target small,
.drive-cv-target small {
  margin-top: 3px;
  color: var(--light-text);
  font-size: .72rem;
  line-height: 1.35;
}

.drive-link-current > .iconify {
  width: 22px;
  height: 22px;
  flex: 0 0 22px;
  color: var(--drive-blue);
}

.drive-activity-head {
  display: flex;
  align-items: center;
  gap: 12px;
  padding-bottom: 15px;
  border-bottom: 1px solid var(--fade-grey, #e9edf2);
}

.drive-activity-head > div {
  display: flex;
  min-width: 0;
  flex-direction: column;
}

.drive-activity-head strong {
  overflow: hidden;
  color: var(--dark-text);
  font-size: .95rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.drive-activity-head span {
  margin-top: 2px;
  color: var(--light-text);
  font-size: .75rem;
}

.drive-activity-empty {
  padding: 36px 12px;
  color: var(--light-text);
  text-align: center;
}

.drive-timeline {
  max-height: 430px;
  padding: 14px 0;
  overflow-y: auto;
}

.drive-timeline-item {
  position: relative;
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 8px 4px 14px;
}

.drive-timeline-item:not(:last-child)::after {
  position: absolute;
  top: 39px;
  bottom: -3px;
  left: 18px;
  width: 1px;
  background: var(--fade-grey, #dfe3e8);
  content: '';
}

.drive-timeline-dot {
  z-index: 1;
  display: grid;
  width: 36px;
  height: 36px;
  flex: 0 0 36px;
  place-items: center;
  border-radius: 50%;
  color: #0b57d0;
  background: #e8f0fe;
}

.drive-timeline-dot .iconify {
  width: 18px;
  height: 18px;
}

.drive-timeline-main {
  display: flex;
  min-width: 0;
  flex: 1;
  flex-direction: column;
}

.drive-timeline-main strong {
  color: var(--dark-text);
  font-size: .82rem;
}

.drive-timeline-main p {
  margin: 2px 0 0;
  color: var(--light-text);
  font-size: .77rem;
}

.drive-timeline-main .drive-timeline-description {
  margin-top: 7px;
  padding: 7px 9px;
  border-radius: 8px;
  color: var(--dark-text);
  background: var(--widget-grey, #f4f5f7);
  font-size: .74rem;
  line-height: 1.4;
}

.drive-timeline-main span {
  margin-top: 3px;
  color: var(--light-text);
  font-size: .7rem;
}

.drive-timeline-main .drive-timeline-file {
  color: var(--drive-blue);
  font-weight: 600;
}

.drive-timeline-side {
  display: flex;
  flex: 0 0 auto;
  align-items: flex-end;
  flex-direction: column;
  gap: 7px;
}

.drive-revision-actions {
  display: flex;
  align-items: center;
  gap: 6px;
}

.drive-revision-actions button {
  display: inline-flex;
  min-height: 30px;
  align-items: center;
  gap: 5px;
  padding: 5px 9px;
  border: 1px solid var(--drive-border);
  border-radius: 8px;
  color: var(--drive-blue);
  background: var(--drive-surface);
  cursor: pointer;
  font-family: inherit;
  font-size: .7rem;
  font-weight: 650;
}

.drive-revision-actions button:hover {
  border-color: var(--drive-blue);
  background: var(--drive-blue-soft);
}

.drive-revision-actions .iconify {
  width: 15px;
  height: 15px;
}

.drive-revision-pill {
  padding: 4px 7px;
  border-radius: 99px;
  color: #0b57d0;
  background: #e8f0fe;
  font-size: .68rem;
  font-weight: 650;
}

@media only screen and (max-width: 560px) {
  .drive-timeline-item {
    flex-wrap: wrap;
  }

  .drive-timeline-side {
    width: calc(100% - 48px);
    align-items: flex-start;
    margin-left: 48px;
  }
}

.is-dark .uds-drive-new {
  --drive-bg: #11151b;
  --drive-surface: #20242b;
  --drive-surface-soft: #262b33;
  --drive-border: #3b414b;
  --drive-border-soft: #343a44;
  --drive-text: #edf0f4;
  --drive-muted: #aeb5bf;
  --drive-blue: #a8c7fa;
  --drive-blue-soft: #243a5a;
  --drive-hover: #303641;
  --drive-selected: #284d6b;
  --drive-danger: #ffb4ab;
}

.is-dark .drive-search {
  background: #303641;
}

.is-dark .drive-search:focus-within {
  background: #262b33;
}

.is-dark .drive-file-icon {
  background: #303641;
}

.is-dark .drive-file-icon.is-folder {
  background: #403a24;
}

.is-dark .drive-file-icon.is-link,
.is-dark .drive-file-icon.is-word {
  background: #263d5c;
}

.is-dark .drive-file-icon.is-excel {
  background: #233e31;
}

.is-dark .drive-file-icon.is-pdf {
  background: #4a2c2c;
}

.is-dark .drive-file-icon.is-image {
  background: #3d3150;
}

.is-dark .drive-card.is-locked {
  border-color: #4a4f58;
  background: #292d34;
}

.is-dark .drive-lock-badge {
  color: #ffd875;
  background: #55451f;
}

.is-dark .drive-access-mode.is-selected {
  border-color: #76a9e8;
  background: #243a5a;
}

.is-dark .drive-access-search {
  border-color: #3b414b;
  background: #20242b;
}

.is-dark .drive-access-note {
  color: #ffe29a;
  background: #493d21;
}

.is-dark .drive-content.is-dragover {
  background: #1f3048;
}

.is-dark .drive-drop-overlay {
  background: rgba(36, 58, 90, .96);
}

.is-dark .drive-revision-target,
.is-dark .drive-revision-selected {
  border-color: #3b414b;
  background: #262b33;
}

.is-dark .drive-revision-picker {
  border-color: #a8c7fa;
  color: #a8c7fa;
  background: #202b3b;
}

.is-dark .drive-revision-picker:hover {
  background: #243a5a;
}

.is-dark .drive-revision-selected > .iconify {
  color: #a8c7fa;
}

.is-dark .drive-revision-target .drive-revision-progress {
  color: #a8c7fa;
  background: #243a5a;
}

.is-dark .drive-timeline-main .drive-timeline-description {
  background: #303641;
}

.is-dark .drive-revision-selected button:hover {
  background: #343a44;
}

@media only screen and (max-width: 960px) {
  .drive-toolbar {
    grid-template-columns: 1fr auto;
  }

  .drive-search {
    grid-column: 1 / -1;
    grid-row: 1;
  }

  .drive-create-actions {
    grid-column: 1;
    grid-row: 2;
  }

  .drive-view-actions {
    grid-column: 2;
    grid-row: 2;
  }
}

@media only screen and (max-width: 700px) {
  .drive-page-head,
  .drive-space-head {
    align-items: flex-start;
    flex-direction: column;
  }

  .drive-head-badge {
    width: 100%;
  }

  .drive-space-head,
  .drive-toolbar,
  .drive-breadcrumbs,
  .drive-trash-note,
  .drive-content,
  .drive-space-foot {
    padding-right: 13px;
    padding-left: 13px;
  }

  .drive-toolbar {
    display: flex;
    align-items: stretch;
    flex-direction: column;
  }

  .drive-create-actions,
  .drive-view-actions {
    flex-wrap: wrap;
  }

  .drive-view-actions {
    justify-content: flex-end;
  }

  .drive-search {
    order: -1;
  }

  .drive-access-modes {
    grid-template-columns: 1fr;
  }

  .drive-access-users-head {
    align-items: stretch;
    flex-direction: column;
  }

  .drive-access-search {
    width: 100%;
  }

  .drive-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .drive-space-foot {
    align-items: flex-start;
    flex-direction: column;
  }

  .drive-search-caption {
    display: none;
  }
}

@media only screen and (max-width: 430px) {
  .drive-grid {
    grid-template-columns: 1fr;
  }

  .drive-head-actions,
  .drive-head-actions .button {
    width: 100%;
  }
}
</style>
