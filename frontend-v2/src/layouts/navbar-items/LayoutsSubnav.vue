<script setup lang="ts">
import { computed, ref } from 'vue'
import { useStorage } from '@vueuse/core'
import Sidebar from 'primevue/sidebar';
import { useRoute, useRouter } from 'vue-router';
export type SubnavId =
  | 'closed'
  | 'home'
  | 'layouts'
  | 'elements'
  | 'components'
  | 'search'
export type Menu = Model.Global.Menu
type TabId = 'list' | 'grid' | 'app' | 'utility'
const activeTab = ref<TabId>('list')

const core_listMenu: any = useStorage('list_menu', [])
const filter = ref('')
const activeSubnav = ref<SubnavId>('closed')
const router = useRouter()
const route = useRoute()
const flatDataArray = (array: any[]) => {
  let dataArray: any = []
  array.forEach((node, _index, _object) => {
    if (node.children) {
      dataArray = dataArray.concat(flatDataArray(node.children))
    } else {
      dataArray.push({
        label: node.name,
        routerLink: node.link,
      })
    }
  })
  return dataArray
}
let setMenu = ref([])
setMenu.value = flatDataArray(core_listMenu.value)
// console.log(core_listMenu)
const toggleSubnav = (subnav: SubnavId) => {
  if (activeSubnav.value === subnav) {
    activeSubnav.value = 'closed'
  } else {
    activeSubnav.value = subnav
  }
}
const clearModel = () => {
  filter.value = ''
}
// const filteredMenu = computed(() => {
//   if (!filter.value) {
//     return core_listMenu.value
//   }
//   var filtered: any = [];

//   for (let i = 0; i < core_listMenu.value.length; i++) {
//     const element = core_listMenu.value[i];

//     filtered.push({
//       'icon': element.icon,
//       'id': element.id,
//       'ishide': element.ishide,
//       'name':element.name,
//       'link':element.link,
//       'parent_id':element.parent_id,
//       'children': []
//     })
//     for (let ii = 0; ii < element.children.length; ii++) {
//       const element2 = element.children[ii];
//       if (element2.name.match(new RegExp(filter.value, 'i'))) {
//         filtered[filtered.length - 1].children.push(element2)
//       }
//     }
//   }
//   return filtered;
//   if (!filter.value) {
//     return setMenu
//   }
//   return setMenu.filter((user: any) => {
//     return user.label.toLowerCase().includes(filter.value.toLowerCase())
//   })
// })
const filteredMenu = computed(() => {
  if (!filter.value) {
    return core_listMenu.value;
  }

  const filtered = [];

  for (let i = 0; i < core_listMenu.value.length; i++) {
    const parent = core_listMenu.value[i];
    const matchedChildren = [];
    if (parent.name.match(new RegExp(filter.value, 'i'))) {
      filtered.push({
        'id': parent.id,
        'parent_id': parent.parent_id,
        'name': parent.name,
        'icon': parent.icon,
        'ishide': parent.ishide,
        'children': parent.children
      });
    } else {
      for (let j = 0; j < parent.children.length; j++) {
        const child = parent.children[j];
        if (child.name.match(new RegExp(filter.value, 'i'))) {
          matchedChildren.push(child);
        }
      }
      if (matchedChildren.length > 0) {
        filtered.push({
          'id': parent.id,
          'parent_id': parent.parent_id,
          'name': parent.name,
          'icon': parent.icon,
          'ishide': parent.ishide,
          'children': matchedChildren
        });
      }
    }
  }

  return filtered;
});

const openMenu = (link: any) => {
  try {
    router.push({ name: link })
  } catch (error) {
    console.error(error)
    window.location.href = `/404${route.fullPath}`
  }

}
</script>

<template>
  <div class="navbar-subnavbar-inner tabs-wrapper">
    <div class="tabs-inner">
      <div class="tabs is-centered is-4">
        <ul>
          <li :class="[activeTab === 'list' && 'is-active']">
            <div class="field">
              <div class="control has-icon">
                <input v-model="filter" type="text" class="input is-rounded search-input"
                  placeholder="Search navigation..." />
                <div class="form-icon">
                  <i aria-hidden="true" class="iconify" data-icon="feather:search"></i>
                </div>
                <div class="form-icon is-right" tabindex="0" @click="clearModel()">
                  <i aria-hidden="true" class="iconify" data-icon="feather:x"></i>
                </div>
              </div>
              <div class="control has-icon">
                <div v-if="filteredMenu.length > 0" class="search-results has-slimscroll is-active">
                  <div v-for="user in filteredMenu" :key="user.label" class="search-result">
                    <div class="meta">
                      <span>{{ user.label }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </div>
    <div class="container">
      <div id="list-pages-tab" class="tab-content" :class="[activeTab === 'list' && 'is-active']">
        <div class="tab-content-inner" style="height: 530px;">
          <div class="center has-slimscroll">
            <div class="columns">
              <div class="column is-3" v-for="(items, key) in filteredMenu" :key="key">
                <h4 class="column-heading">{{ items.name }}</h4>
                <ul>
                  <li v-for="(items2, key2) in items.children" :key="key2">
                    <!-- <RouterLink :to="{ name: items2.link }"> -->
                    <a @click="openMenu(items2.link)">
                      <i :class="['lnil lnil-' + items.icon]" aria-hidden="true"></i>
                      <span>{{ items2.name }}</span>
                      <i aria-hidden="true" class="iconify" data-icon="feather:chevron-down"></i>
                    </a>
                    <!-- </RouterLink> -->
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss">
@import '/@src/scss/abstracts/mixins';
@import '/@src/scss/layout/navbar';
@import '/@src/scss/layout/responsive';

.field .control .form-icon.is-right {
  left: unset !important;
  right: 6px;
  cursor: pointer;
}

.control .search-results.is-active {
  opacity: 1;
  pointer-events: all;
  transform: translateY(0);
}

.is-dark .search-results {
  background: var(--dark-sidebar-dark-2);
  border-color: var(--dark-sidebar-light-4) !important;
}

.control .search-results {
  position: absolute;
  top: 64px;
  left: 0;
  width: 100%;
  max-height: 285px;
  overflow-y: auto;
  background: var(--white);
  opacity: 0;
  border: 1px solid var(--fade-grey-dark-3);
  border-radius: var(--radius);
  pointer-events: none;
  transform: translateY(5px);
  box-shadow: var(--light-box-shadow);
  z-index: 10;
  transition: all 0.3s;
}

.has-slimscroll {
  overflow-y: auto;
}
</style>
