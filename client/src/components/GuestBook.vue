<script setup>
import { ref, onMounted } from 'vue';
import { message } from 'ant-design-vue';

const baseURL = '/API/guestBook';

// 留言数据
const comments = ref([]);
const loading = ref(false);
const submitting = ref(false);

// 表单数据
const formData = ref({
    name: '',
    email: '',
    content: ''
});

// 获取留言列表
const fetchComments = async () => {
    loading.value = true;
    try {
        const response = await fetch(baseURL);
        const data = await response.json();
        if (data.success) {
            comments.value = data.data;
        } else {
            message.error(data.error || '获取留言失败');
        }
    } catch (error) {
        message.error('网络错误: ' + error.message);
    } finally {
        loading.value = false;
    }
};

// 提交留言
const submitComment = async () => {
    // 验证表单
    if (!formData.value.name.trim()) {
        message.warning('请输入昵称');
        return;
    }

    if (!formData.value.email.trim()) {
        message.warning('请输入邮箱');
        return;
    }

    if (!formData.value.content.trim()) {
        message.warning('请输入留言内容');
        return;
    }

    // 邮箱格式验证
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(formData.value.email)) {
        message.warning('请输入有效的邮箱地址');
        return;
    }

    submitting.value = true;

    try {
        const response = await fetch(baseURL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                user_name: formData.value.name,
                user_email: formData.value.email,
                content: formData.value.content
            })
        });

        const data = await response.json();

        if (data.success) {
            message.success('留言提交成功');
            // 清空表单
            formData.value = {
                name: '',
                email: '',
                content: ''
            };
            // 刷新留言列表
            await fetchComments();
        } else {
            message.error(data.error || '留言提交失败');
        }
    } catch (error) {
        message.error('提交失败: ' + error.message);
    } finally {
        submitting.value = false;
    }
};

// 格式化日期
const formatDate = (dateString) => {
    const options = {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return new Date(dateString).toLocaleDateString('zh-CN', options);
};

// 组件挂载时获取留言列表
onMounted(() => {
    fetchComments();
});
</script>

<template>
    <a-space direction="vertical" style="width: 100%">
        <a-row :gutter="16">
            <a-col :span="12">
                <a-input v-model:value="formData.name" placeholder="昵称" :maxlength="20" />
            </a-col>
            <a-col :span="12">
                <a-input v-model:value="formData.email" placeholder="邮箱" type="email" />
            </a-col>
        </a-row>

        <a-textarea v-model:value="formData.content" placeholder="留言内容" :auto-size="{ minRows: 3, maxRows: 6 }"
            :maxlength="200" show-count />

        <a-flex justify="flex-end">
            <a-button type="primary" @click="submitComment" :loading="submitting">
                提交评论
            </a-button>
        </a-flex>
    </a-space>

    <a-divider />

    <a-list :data-source="comments" :loading="loading" item-layout="vertical" size="large">
        <template #renderItem="{ item }">
            <a-list-item>
                <template #actions>
                    <a-tooltip>
                        <template #title>{{ item.user_agent }}</template>
                        <span>IP: {{ item.ip_address }}丨{{ formatDate(item.created_at) }}</span>
                    </a-tooltip>
                </template>

                <a-list-item-meta>
                    <template #title>
                        {{ item.user_name }}
                    </template>
                    <template #avatar>
                        <a-avatar style="background-color: #1890ff">
                            {{ item.user_name.charAt(0).toUpperCase() }}
                        </a-avatar>
                    </template>
                </a-list-item-meta>

                <div class="comment-content">
                    {{ item.content }}
                </div>
            </a-list-item>
        </template>

        <template #empty>
            <a-empty />
        </template>
    </a-list>

</template>

<style scoped>
.comment-content {
    margin-top: 12px;
    padding-left: 36px;
    white-space: pre-wrap;
    word-break: break-word;
}

:deep(.ant-list-item-meta-title) {
    margin-bottom: 0;
}

:deep(.ant-list-item-meta-description) {
    color: rgba(0, 0, 0, 0.65);
}
</style>