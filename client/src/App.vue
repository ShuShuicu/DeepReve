<script setup>
import { ref } from 'vue';
const activeKey = ref('tab1'); // 简化变量名
const onTabChange = (key) => {
  activeKey.value = key;
};
</script>

<template>
  <header>
    <AppHeader />
  </header>
  <main>
    <a-card style="width: 100%">
      <a-tabs v-model:activeKey="activeKey" @change="onTabChange">
        <a-tab-pane key="tab1" tab="文件列表">
          <template #tab>
            <span>
              文件列表
            </span>
          </template>
          <FileList />
        </a-tab-pane>
        
        <a-tab-pane key="tab2" tab="留言帮助">
          <GuestBook />
        </a-tab-pane>
      </a-tabs>
    </a-card>
  </main>
</template>