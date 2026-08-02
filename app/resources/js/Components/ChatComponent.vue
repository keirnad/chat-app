<script setup>
    import { ref, onMounted, watch, nextTick } from "vue";
    import axios from 'axios';

    const props = defineProps({
        user: {
            type: Object,
            required: true
        },
        currentUser: {
            type: Object,
            required: true
        }
    });

    const messages = ref([]);
    const newMessage = ref('');
    const messageContainer = ref(null);
    const isUserTyping = ref(false);
    const isUserTypingTimer = ref(null);
    const isUserOnline = ref(false);

    watch(
        messages,
        () => {
            nextTick(()=>{
                messageContainer.value.scrollTo({
                    top: messageContainer.value.scrollHeight,
                    behavior: "smooth",
                })
            })
        },
        { deep: true }
    );

    const fetchMessages = async () => {
        try {
            const response = await axios.get(`/messages/${props.user.id}`);
            messages.value = response.data;
        } catch (error) {
            console.error("Failed to fetch messages:", error);
        }
    };

    const sendMessage = async () => {
        if (newMessage.value.trim() !== '') {
            try {
                const response = await axios.post(`/messages/${props.user.id}`, {
                    message: newMessage.value,
                });
                messages.value.push(response.data);
                newMessage.value = '';
            }   catch (error) {
                console.error("Failed to send message:", error)
            }
        }
    };

    const sendTypingEvent = () => {
        Echo.private(`chat.${props.user.id}`).whisper("typing", {
            userID: props.currentUser.id,
        });
    };

    const formatTime = (datetime) => {
        const options = {hour: '2-digit', minute: '2-digit'};
        return new Date(datetime).toLocaleTimeString([], options);
    };

    onMounted(() => {
        fetchMessages();

        Echo.join(`presence.chat`)
            .here(users => {
                isUserOnline.value = users.some(user => user.id === props.user.id);
            })
            .joining(user => {
                if (user.id === props.user.id) isUserOnline.value = true;
            })
            .leaving(user => {
                if (user.id === props.user.id) isUserOnline.value = false;
            });

        Echo.private(`chat.${props.currentUser.id}`)
            .listen("MessageSent", (response) => {
                messages.value.push(response.message);
            })
            .listenForWhisper("typing", (response) => {
                isUserTyping.value = response.userID === props.user.id;

                if (isUserTypingTimer.value) {
                    clearTimeout(isUserTypingTimer.value);
                }

                isUserTypingTimer.value = setTimeout(() => {
                    isUserTyping.value = false;
                }, 1000)
            })
    })
</script>

<template>

</template>

<style scoped>

</style>
