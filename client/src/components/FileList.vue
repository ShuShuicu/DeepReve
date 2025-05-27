<script setup>
import { ref, onMounted } from 'vue';
import { message } from 'ant-design-vue';
import { FolderOutlined, FileOutlined, DownloadOutlined } from '@ant-design/icons-vue';

const baseURL = '/API/getFileList';
const downloadURL = '/API/getFileDownload';

const fileList = ref([]);
const currentFile = ref(null);
const visible = ref(false);
const loading = ref(false);
const activeKeys = ref([]);

// 获取文件列表
const fetchFileList = async () => {
    loading.value = true;
    try {
        const response = await fetch(baseURL);
        const data = await response.json();

        if (data.success) {
            fileList.value = data.data.folders.map(folder => ({
                ...folder,
                isFolder: true,
                loaded: !!folder.items
            }));

            fileList.value.push(...data.data.files.map(file => ({
                ...file,
                isFolder: false
            })));
        }
    } catch (error) {
        message.error('获取文件列表失败: ' + error.message);
    } finally {
        loading.value = false;
    }
};

// 获取文件夹内容
const fetchFolderContent = async (folder) => {
    try {
        if (folder.items) {
            const index = fileList.value.findIndex(item => 
                item.isFolder && item.name === folder.name
            );
            if (index !== -1) {
                fileList.value[index].loaded = true;
            }
            return;
        }

        const response = await fetch(`${baseURL}?path=${encodeURIComponent(folder.name)}`);
        const data = await response.json();

        if (data.success) {
            const index = fileList.value.findIndex(item =>
                item.isFolder && item.name === folder.name
            );

            if (index !== -1) {
                fileList.value[index].items = {
                    folders: data.data.folders.map(f => ({ 
                        ...f, 
                        isFolder: true, 
                        loaded: false
                    })),
                    files: data.data.files.map(f => ({ 
                        ...f, 
                        isFolder: false
                    }))
                };
                fileList.value[index].loaded = true;
            }
        }
    } catch (error) {
        message.error('获取文件夹内容失败: ' + error.message);
    }
};

// 处理折叠面板变化
const handlePanelChange = (keys) => {
    activeKeys.value = keys;
    if (keys.length > 0) {
        const lastKey = keys[keys.length - 1];
        const folder = fileList.value.find(item => item.isFolder && item.name === lastKey);
        if (folder) {
            fetchFolderContent(folder);
        }
    }
};

// 处理文件点击
const handleFileClick = (file) => {
    currentFile.value = file;
    visible.value = true;
};

// 下载文件
const downloadFile = (file) => {
    if (!file?.dir) {
        message.error('无法获取文件路径');
        return;
    }
    
    // 创建隐藏iframe触发下载
    const iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.src = `${downloadURL}?name=${encodeURIComponent(file.dir)}`;
    document.body.appendChild(iframe);
    
    // 更新本地显示
    setTimeout(() => {
        if (currentFile.value && currentFile.value.dir === file.dir) {
            currentFile.value.download_count = (currentFile.value.download_count || 0) + 1;
            currentFile.value.last_download_time = new Date().toISOString();
        }
        document.body.removeChild(iframe);
    }, 1000);
};

onMounted(() => {
    fetchFileList();
});
</script>

<template>
        <a-spin :spinning="loading">
            <a-collapse v-model:activeKey="activeKeys" @change="handlePanelChange">
                <template v-for="item in fileList" :key="item.name">
                    <a-collapse-panel 
                        v-if="item.isFolder" 
                        :key="item.name"
                    >
                        <template #header>
                            <FolderOutlined style="margin-right: 8px; color: #1890ff;" />
                            {{ item.name }}
                        </template>
                        
                        <div v-if="item.loaded && item.items" class="folder-content">
                            <div 
                                v-for="subFolder in item.items.folders" 
                                :key="subFolder.name" 
                                class="folder-item"
                                @click.stop=""
                            >
                                <FolderOutlined style="margin-right: 8px; color: #1890ff;" />
                                <span>{{ subFolder.name }}</span>
                            </div>
                            
                            <div 
                                v-for="file in item.items.files" 
                                :key="file.name" 
                                class="file-item"
                                @click.stop="handleFileClick(file)"
                            >
                                <FileOutlined style="margin-right: 8px;" />
                                <span>{{ file.name }}</span>
                            </div>
                            
                            <div 
                                v-if="item.items.folders.length === 0 && item.items.files.length === 0"
                                class="empty-folder"
                            >
                                空文件夹
                            </div>
                        </div>
                        
                        <div v-else class="loading-placeholder">
                            <a-spin size="small" />
                        </div>
                    </a-collapse-panel>
                    
                    <div 
                        v-else 
                        class="file-item top-level-file"
                        @click="handleFileClick(item)"
                    >
                        <FileOutlined style="margin-right: 8px;" />
                        <span>{{ item.name }}</span>
                    </div>
                </template>
            </a-collapse>
        </a-spin>

        <a-modal 
            v-model:visible="visible" 
            :title="currentFile?.name || '文件详情'"
            width="600px"
            :footer="null"
        >
            <div v-if="currentFile" class="file-detail">
                <a-descriptions bordered :column="1">
                    <a-descriptions-item label="文件名">{{ currentFile.name }}</a-descriptions-item>
                    <a-descriptions-item label="类型">{{ currentFile.type }}</a-descriptions-item>
                    <a-descriptions-item label="大小">{{ currentFile.size }}</a-descriptions-item>
                    <a-descriptions-item label="修改时间">{{ currentFile.modified }}</a-descriptions-item>
                    <a-descriptions-item label="扩展名">{{ currentFile.extension }}</a-descriptions-item>
                    <a-descriptions-item label="下载次数">{{ currentFile.download_count }}</a-descriptions-item>
                    <a-descriptions-item label="最后下载时间">
                        {{ currentFile.last_download_time || '从未下载' }}
                    </a-descriptions-item>
                </a-descriptions>
                
                <div class="download-btn-container">
                    <a-button 
                        type="primary" 
                        @click="downloadFile(currentFile)"
                    >
                        <DownloadOutlined />
                        下载文件
                    </a-button>
                </div>
            </div>
        </a-modal>
</template>

<style scoped>

.folder-content {
    padding-left: 24px;
}

.file-item, .folder-item {
    padding: 8px 0;
    cursor: pointer;
    transition: all 0.3s;
}

.file-item:hover {
    background-color: #f5f5f5;
}

.top-level-file {
    padding: 12px 16px;
    border-bottom: 1px solid #f0f0f0;
}

.loading-placeholder {
    display: flex;
    justify-content: center;
    padding: 8px 0;
}

.file-detail {
    padding: 10px;
}

.download-btn-container {
    margin-top: 20px;
    text-align: center;
}

.empty-folder {
    color: #999;
    padding: 8px 0;
    font-style: italic;
}
</style>