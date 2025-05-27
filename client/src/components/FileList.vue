<script setup>
import { ref, onMounted } from 'vue';
import { message } from 'ant-design-vue';
import { FolderOutlined, FileOutlined, DownloadOutlined } from '@ant-design/icons-vue';

const baseURL = 'http://deepreve-server.localhost:91/API/getFileList';
const downloadURL = 'http://deepreve-server.localhost:91/API/getFileDownload';

const fileList = ref([]);
const currentFile = ref(null);
const visible = ref(false);
const loading = ref(false);
const activeKeys = ref([]);

// 生成唯一key（使用完整路径）
const generateKey = (path) => {
  return path.replace(/\//g, '|');
};

// 获取文件列表
const fetchFileList = async () => {
    loading.value = true;
    try {
        const response = await fetch(baseURL);
        const data = await response.json();

        if (data.success) {
            // 处理文件夹数据
            fileList.value = data.data.folders.map(folder => ({
                ...folder,
                isFolder: true,
                loaded: !!folder.items,
                key: generateKey(folder.name),
                items: folder.items ? {
                    folders: folder.items.folders.map(subFolder => ({
                        ...subFolder,
                        isFolder: true,
                        loaded: !!subFolder.items,
                        key: generateKey(`${folder.name}/${subFolder.name}`)
                    })),
                    files: folder.items.files.map(file => ({
                        ...file,
                        isFolder: false,
                        key: generateKey(`${folder.name}/${file.name}`)
                    }))
                } : null
            }));

            // 处理根目录文件
            fileList.value.push(...data.data.files.map(file => ({
                ...file,
                isFolder: false,
                key: generateKey(file.name)
            })));
        }
    } catch (error) {
        message.error('获取文件列表失败: ' + error.message);
    } finally {
        loading.value = false;
    }
};

// 获取文件夹内容
const fetchFolderContent = async (folderPath, folderKey) => {
    try {
        const response = await fetch(`${baseURL}?path=${encodeURIComponent(folderPath)}`);
        const data = await response.json();

        if (data.success) {
            // 更新文件列表中的对应文件夹
            const updateFolderItems = (items) => {
                return items.map(item => {
                    if (item.key === folderKey) {
                        return {
                            ...item,
                            loaded: true,
                            items: {
                                folders: data.data.folders.map(subFolder => ({
                                    ...subFolder,
                                    isFolder: true,
                                    loaded: false,
                                    key: generateKey(`${folderPath}/${subFolder.name}`)
                                })),
                                files: data.data.files.map(file => ({
                                    ...file,
                                    isFolder: false,
                                    key: generateKey(`${folderPath}/${file.name}`)
                                }))
                            }
                        };
                    }
                    if (item.isFolder && item.items) {
                        return {
                            ...item,
                            items: {
                                ...item.items,
                                folders: updateFolderItems(item.items.folders)
                            }
                        };
                    }
                    return item;
                });
            };

            fileList.value = updateFolderItems(fileList.value);
        }
    } catch (error) {
        message.error(`获取文件夹 ${folderPath} 内容失败: ` + error.message);
    }
};

// 处理折叠面板变化
const handlePanelChange = (keys) => {
    activeKeys.value = keys;
    if (keys.length > 0) {
        const lastKey = keys[keys.length - 1];
        const folderPath = lastKey.replace(/\|/g, '/');
        
        // 查找未加载的文件夹
        const findUnloadedFolder = (items) => {
            for (const item of items) {
                if (item.key === lastKey && item.isFolder && !item.loaded) {
                    return item;
                }
                if (item.isFolder && item.items) {
                    const found = findUnloadedFolder(item.items.folders);
                    if (found) return found;
                }
            }
            return null;
        };
        
        const folder = findUnloadedFolder(fileList.value);
        if (folder) {
            fetchFolderContent(folderPath, lastKey);
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
    <div class="file-manager-container">
        <a-spin :spinning="loading">
            <!-- 主折叠面板 -->
            <a-collapse v-model:activeKey="activeKeys" @change="handlePanelChange" accordion>
                <!-- 递归渲染文件夹结构 -->
                <template v-for="item in fileList" :key="item.key">
                    <!-- 文件夹项 -->
                    <a-collapse-panel 
                        v-if="item.isFolder" 
                        :key="item.key"
                    >
                        <template #header>
                            <FolderOutlined style="margin-right: 8px; color: #1890ff;" />
                            {{ item.name }}
                        </template>
                        
                        <!-- 文件夹内容 -->
                        <div v-if="item.loaded && item.items" class="folder-content">
                            <!-- 子文件夹 -->
                            <a-collapse v-model:activeKey="activeKeys" @change="handlePanelChange">
                                <a-collapse-panel 
                                    v-for="subFolder in item.items.folders" 
                                    :key="subFolder.key"
                                >
                                    <template #header>
                                        <FolderOutlined style="margin-right: 8px; color: #1890ff;" />
                                        {{ subFolder.name }}
                                    </template>
                                    
                                    <div v-if="subFolder.loaded && subFolder.items" class="folder-content">
                                        <!-- 这里可以继续递归渲染更深层级的文件夹和文件 -->
                                        <div 
                                            v-for="file in subFolder.items.files" 
                                            :key="file.key" 
                                            class="file-item"
                                            @click.stop="handleFileClick(file)"
                                        >
                                            <FileOutlined style="margin-right: 8px;" />
                                            <span>{{ file.name }}</span>
                                        </div>
                                    </div>
                                    
                                    <div v-else class="loading-placeholder">
                                        <a-spin size="small" />
                                    </div>
                                </a-collapse-panel>
                            </a-collapse>
                            
                            <!-- 当前文件夹下的文件 -->
                            <div 
                                v-for="file in item.items.files" 
                                :key="file.key" 
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
                    
                    <!-- 文件项 -->
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

        <!-- 文件详情对话框 -->
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
    </div>
</template>

<style scoped>
.file-manager-container {
    padding: 20px;
    background: #fff;
    border-radius: 4px;
}

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