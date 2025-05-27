<script setup>
const title = 'DeepReve';
const subTitle = '基于Vue3+PHP开发的简单(陋)下载盘';

</script>

<template>
    <div>
        <a-page-header class="demo-page-header" style="border: 1px solid rgb(235, 237, 240)" :title="title"
            :sub-title="subTitle">
            <template #extra>
                <a-button key="2" href="https://space.bilibili.com/435502585" target="_blank">哔哩哔哩</a-button>
                <a-button key="1" href="https://github.com/ShuShuicu/DeepReve" target="_blank" type="primary">GitHub</a-button>
            </template>
        </a-page-header>
    </div>
</template>

<style scoped>
.demo-page-header :deep(tr:last-child td) {
    padding-bottom: 0;
}
</style>