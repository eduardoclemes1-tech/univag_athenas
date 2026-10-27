document.addEventListener('DOMContentLoaded', function () {
    const app = document.getElementById('chatApp');
    if (!app) return;

    const api = app.dataset.api;
    const notificationsApi = app.dataset.notificationsApi;
    const csrfToken = app.dataset.csrf;
    const currentUserId = Number(app.dataset.userId);
    const currentUserType = app.dataset.userType;
    const conversationList = document.getElementById('chatConversationList');
    const messageList = document.getElementById('chatMessageList');
    const recentList = document.getElementById('chatRecentes');
    const notificationList = document.getElementById('notificationItems');
    const notificationBadge = document.getElementById('notificationUnreadBadge');
    const unreadChatBadge = document.getElementById('chatUnreadBadge');
    const contactSelect = document.getElementById('chatContactSelect');
    const messageInput = document.getElementById('chatMessageInput');
    const composeForm = document.getElementById('chatCompose');
    const appointmentDialog = document.getElementById('chatScheduleDialog');
    const appointmentForm = document.getElementById('chatScheduleForm');
    const chatStatus = document.getElementById('chatConnectionStatus');

    let conversations = [];
    let contacts = [];
    let selectedConversation = null;
    let latestMessageId = 0;
    let refreshing = false;

    function escapeText(value) {
        return String(value ?? '');
    }

    function initials(name) {
        return escapeText(name).trim().split(/\s+/).slice(0, 2).map(function (part) {
            return part.charAt(0).toLocaleUpperCase('pt-BR');
        }).join('') || '?';
    }

    function formatTime(value) {
        if (!value) return '';
        const date = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) return '';
        return new Intl.DateTimeFormat('pt-BR', { hour: '2-digit', minute: '2-digit' }).format(date);
    }

    function formatDate(value) {
        if (!value) return '';
        const date = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) return escapeText(value);
        return new Intl.DateTimeFormat('pt-BR', {
            dateStyle: 'medium',
            timeStyle: 'short'
        }).format(date);
    }

    function setBadge(badge, count) {
        badge.textContent = count > 99 ? '99+' : String(count);
        badge.hidden = count < 1;
    }

    async function request(url, options) {
        const response = await fetch(url, options);
        const payload = await response.json().catch(function () {
            return {};
        });
        if (!response.ok) {
            throw new Error(payload.erro || 'Não foi possível concluir a solicitação.');
        }
        return payload;
    }

    async function post(url, payload) {
        return request(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify(payload)
        });
    }

    function showToast(message, error) {
        let toast = document.getElementById('athenasToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'athenasToast';
            toast.className = 'athenas-toast';
            toast.setAttribute('role', 'status');
            document.body.appendChild(toast);
        }
        toast.textContent = message;
        toast.classList.toggle('toast-error', Boolean(error));
        toast.classList.add('toast-visible');
        window.clearTimeout(showToast.timeoutId);
        showToast.timeoutId = window.setTimeout(function () {
            toast.classList.remove('toast-visible');
        }, 3600);
    }

    function openChat(conversationId) {
        app.classList.add('chat-open');
        app.setAttribute('aria-hidden', 'false');
        document.getElementById('messagesDropdown').classList.remove('active');
        document.getElementById('notificationsDropdown').classList.remove('active');
        document.getElementById('chatToggleSize').setAttribute('aria-label', 'Expandir chat');
        document.getElementById('chatToggleSize').title = 'Expandir chat';
        if (conversationId) {
            const conversation = conversations.find(function (item) {
                return Number(item.id) === Number(conversationId);
            });
            if (conversation) selectConversation(conversation);
        }
    }

    function closeChat() {
        app.classList.remove('chat-open', 'chat-fullscreen');
        app.setAttribute('aria-hidden', 'true');
    }

    function renderConversations() {
        const term = document.getElementById('chatSearch').value.trim().toLocaleLowerCase('pt-BR');
        const visible = conversations.filter(function (conversation) {
            return escapeText(conversation.contato_nome).toLocaleLowerCase('pt-BR').includes(term);
        });
        conversationList.replaceChildren();

        if (!visible.length) {
            const empty = document.createElement('p');
            empty.className = 'chat-empty-state';
            empty.textContent = conversations.length
                ? 'Nenhuma conversa corresponde à busca.'
                : currentUserType === 'aluno'
                    ? 'Escolha um monitor acima para iniciar uma conversa.'
                    : 'As conversas com alunos aparecerão aqui.';
            conversationList.appendChild(empty);
        }

        visible.forEach(function (conversation) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'chat-conversation-item';
            button.classList.toggle('conversation-selected', Number(conversation.id) === Number(selectedConversation?.id));

            const avatar = document.createElement('span');
            avatar.className = 'chat-avatar';
            avatar.textContent = initials(conversation.contato_nome);
            const summary = document.createElement('span');
            summary.className = 'chat-conversation-summary';
            const name = document.createElement('strong');
            name.textContent = conversation.contato_nome;
            const preview = document.createElement('span');
            preview.textContent = conversation.ultima_mensagem || 'Conversa iniciada';
            const time = document.createElement('small');
            time.textContent = formatTime(conversation.ultima_mensagem_em || conversation.atualizado_em);
            summary.append(name, preview);
            button.append(avatar, summary, time);

            if (Number(conversation.nao_lidas) > 0) {
                const unread = document.createElement('span');
                unread.className = 'chat-unread-dot';
                unread.setAttribute('aria-label', 'Mensagens não lidas');
                button.appendChild(unread);
            }
            button.addEventListener('click', function () {
                selectConversation(conversation);
            });
            conversationList.appendChild(button);
        });

        recentList.replaceChildren();
        if (!conversations.length) {
            const empty = document.createElement('p');
            empty.className = 'popover-empty';
            empty.textContent = currentUserType === 'aluno'
                ? 'Inicie uma conversa pelo chat com um monitor.'
                : 'Nenhuma conversa com alunos ainda.';
            recentList.appendChild(empty);
        }
        conversations.slice(0, 5).forEach(function (conversation) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'message-item';
            button.dataset.conversationId = conversation.id;
            const avatar = document.createElement('span');
            avatar.className = 'msg-avatar';
            avatar.textContent = initials(conversation.contato_nome);
            const content = document.createElement('span');
            content.className = 'msg-content';
            const name = document.createElement('strong');
            name.textContent = conversation.contato_nome;
            const preview = document.createElement('p');
            preview.textContent = conversation.ultima_mensagem || 'Iniciar conversa';
            const time = document.createElement('small');
            time.textContent = formatTime(conversation.ultima_mensagem_em || conversation.atualizado_em);
            content.append(name, preview, time);
            button.append(avatar, content);
            button.addEventListener('click', function () {
                openChat(conversation.id);
            });
            recentList.appendChild(button);
        });

        const totalUnread = conversations.reduce(function (sum, conversation) {
            return sum + Number(conversation.nao_lidas || 0);
        }, 0);
        setBadge(unreadChatBadge, totalUnread);
    }

    function renderContacts() {
        contactSelect.replaceChildren();
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = currentUserType === 'aluno'
            ? 'Escolha um monitor'
            : 'Alunos das suas conversas';
        contactSelect.appendChild(placeholder);

        contacts.forEach(function (contact) {
            const option = document.createElement('option');
            option.value = contact.id;
            option.textContent = contact.nome;
            contactSelect.appendChild(option);
        });
        contactSelect.disabled = currentUserType !== 'aluno' || contacts.length === 0;
        contactSelect.parentElement.hidden = currentUserType !== 'aluno';
    }

    async function loadBootstrap() {
        const data = await request(api + '?acao=inicio');
        conversations = data.conversas || [];
        contacts = data.contatos || [];
        renderConversations();
        renderContacts();

        if (selectedConversation) {
            const updated = conversations.find(function (item) {
                return Number(item.id) === Number(selectedConversation.id);
            });
            if (updated) {
                selectedConversation = updated;
                updateActiveHeader();
            } else {
                selectedConversation = null;
            }
        }
        chatStatus.textContent = 'Atualiza automaticamente';
    }

    function updateActiveHeader() {
        if (!selectedConversation) return;
        document.getElementById('chatActiveAvatar').textContent = initials(selectedConversation.contato_nome);
        document.getElementById('chatActiveName').textContent = selectedConversation.contato_nome;
        document.getElementById('chatActiveSubtitle').textContent = selectedConversation.contato_email || 'Conversa privada';
        document.getElementById('requestAppointment').hidden = currentUserType !== 'aluno';
        composeForm.hidden = false;
    }

    function renderMessages(messages) {
        const welcome = messageList.querySelector('.chat-welcome');
        if (welcome) welcome.remove();

        messages.forEach(function (message) {
            const row = document.createElement('article');
            row.className = 'chat-message-row';
            row.classList.toggle('message-own', Number(message.remetente_id) === currentUserId);
            const bubble = document.createElement('div');
            bubble.className = 'chat-message-bubble';
            const text = document.createElement('p');
            text.textContent = message.conteudo;
            const meta = document.createElement('small');
            meta.textContent = (Number(message.remetente_id) === currentUserId ? 'Você · ' : '')
                + formatTime(message.criada_em);
            bubble.append(text, meta);
            row.appendChild(bubble);
            messageList.appendChild(row);
            latestMessageId = Math.max(latestMessageId, Number(message.id));
        });
        if (messages.length) {
            messageList.scrollTop = messageList.scrollHeight;
        }
    }

    function renderAppointments(appointments) {
        const container = document.getElementById('chatAppointments');
        container.replaceChildren();
        container.hidden = appointments.length === 0;

        appointments.forEach(function (appointment) {
            const card = document.createElement('article');
            card.className = 'chat-appointment-card';
            const heading = document.createElement('div');
            heading.className = 'appointment-heading';
            const title = document.createElement('strong');
            title.textContent = appointment.assunto;
            const status = document.createElement('span');
            status.className = 'appointment-status status-' + appointment.status;
            status.textContent = {
                solicitado: 'Aguardando resposta',
                confirmado: 'Confirmada',
                recusado: 'Recusada',
                cancelado: 'Cancelada'
            }[appointment.status] || appointment.status;
            heading.append(title, status);
            const date = document.createElement('p');
            date.textContent = formatDate(appointment.agendado_para);
            card.append(heading, date);

            if (appointment.link_zoom && appointment.status === 'confirmado') {
                const link = document.createElement('a');
                link.href = appointment.link_zoom;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                link.className = 'appointment-zoom-link';
                link.textContent = 'Entrar na reunião do Zoom';
                card.appendChild(link);
            }

            if (currentUserType === 'monitor' && appointment.status === 'solicitado') {
                const zoomInput = document.createElement('input');
                zoomInput.type = 'url';
                zoomInput.className = 'appointment-zoom-input';
                zoomInput.placeholder = 'Link da reunião Zoom (obrigatório ao confirmar)';
                zoomInput.maxLength = 500;
                zoomInput.setAttribute('aria-label', 'Link da reunião Zoom');
                const actions = document.createElement('div');
                actions.className = 'appointment-actions';
                const accept = document.createElement('button');
                accept.type = 'button';
                accept.className = 'appointment-accept';
                accept.textContent = 'Confirmar';
                accept.addEventListener('click', function () {
                    if (!zoomInput.value.trim()) {
                        zoomInput.focus();
                        showToast('Informe o link do Zoom para confirmar.', true);
                        return;
                    }
                    respondAppointment(appointment, 'confirmado', zoomInput.value.trim());
                });
                const decline = document.createElement('button');
                decline.type = 'button';
                decline.className = 'appointment-decline';
                decline.textContent = 'Recusar';
                decline.addEventListener('click', function () {
                    respondAppointment(appointment, 'recusado', '');
                });
                actions.append(accept, decline);
                card.append(zoomInput, actions);
            }
            container.appendChild(card);
        });
    }

    async function loadMessages(append) {
        if (!selectedConversation) return;
        const sinceId = append ? latestMessageId : 0;
        if (!append) {
            latestMessageId = 0;
            messageList.replaceChildren();
        }
        const data = await request(
            api + '?acao=mensagens&conversa_id=' + encodeURIComponent(selectedConversation.id)
            + '&desde_id=' + sinceId
        );
        renderMessages(data.mensagens || []);
        renderAppointments(data.agendamentos || []);
        if ((data.mensagens || []).length) {
            await loadBootstrap();
        }
    }

    async function selectConversation(conversation) {
        selectedConversation = conversation;
        updateActiveHeader();
        renderConversations();
        openChat();
        document.getElementById('chatActiveName').textContent = conversation.contato_nome;
        try {
            await loadMessages(false);
            messageInput.focus();
        } catch (error) {
            showToast(error.message, true);
        }
    }

    async function refreshNotifications() {
        const data = await request(notificationsApi);
        const notificationData = data.notificacoes || [];
        setBadge(notificationBadge, Number(data.nao_lidas || 0));
        notificationList.replaceChildren();

        if (!notificationData.length) {
            const empty = document.createElement('p');
            empty.className = 'popover-empty';
            empty.textContent = 'Você não tem notificações por enquanto.';
            notificationList.appendChild(empty);
            return;
        }

        notificationData.forEach(function (notification) {
            const item = document.createElement('article');
            item.className = 'notif-item';
            if (!notification.lida_em) item.classList.add('unread');
            const icon = document.createElement('span');
            icon.className = 'notif-icon';
            const iconElement = document.createElement('i');
            iconElement.className = notification.tipo === 'mensagem'
                ? 'fa-solid fa-comment'
                : 'fa-solid fa-calendar-check';
            icon.appendChild(iconElement);
            const content = document.createElement('div');
            content.className = 'notif-content';
            const title = document.createElement('strong');
            title.textContent = notification.titulo;
            const message = document.createElement('p');
            message.textContent = notification.mensagem;
            const time = document.createElement('small');
            time.className = 'notif-time';
            time.textContent = formatDate(notification.criada_em);
            content.append(title, message);

            if (notification.link_zoom && notification.status === 'confirmado') {
                const zoom = document.createElement('a');
                zoom.href = notification.link_zoom;
                zoom.target = '_blank';
                zoom.rel = 'noopener noreferrer';
                zoom.className = 'notification-zoom-link';
                zoom.textContent = 'Abrir link do Zoom';
                content.appendChild(zoom);
            }
            content.appendChild(time);
            item.append(icon, content);
            item.addEventListener('click', function (event) {
                if (event.target.closest('a')) return;
                post(notificationsApi, {
                    acao: 'marcar_lida',
                    notificacao_id: Number(notification.id)
                }).then(refreshNotifications).catch(function (error) {
                    showToast(error.message, true);
                });
                if (notification.conversa_id) openChat(notification.conversa_id);
            });
            notificationList.appendChild(item);
        });
    }

    async function respondAppointment(appointment, status, zoomUrl) {
        try {
            await post(api, {
                acao: 'responder_agendamento',
                conversa_id: selectedConversation.id,
                agendamento_id: appointment.id,
                status: status,
                link_zoom: zoomUrl
            });
            showToast(status === 'confirmado' ? 'Monitoria confirmada e aluno notificado.' : 'Solicitação recusada.');
            await Promise.all([loadMessages(false), refreshNotifications(), loadBootstrap()]);
        } catch (error) {
            showToast(error.message, true);
        }
    }

    document.getElementById('btnMessages').addEventListener('click', function () {
        window.setTimeout(function () {
            if (document.getElementById('messagesDropdown').classList.contains('active')) {
                loadBootstrap().catch(function (error) {
                    showToast(error.message, true);
                });
            }
        }, 0);
    });

    document.getElementById('btnNotifications').addEventListener('click', function () {
        window.setTimeout(function () {
            if (document.getElementById('notificationsDropdown').classList.contains('active')) {
                refreshNotifications().catch(function (error) {
                    showToast(error.message, true);
                });
            }
        }, 0);
    });

    document.querySelectorAll('[data-abrir-chat]').forEach(function (button) {
        button.addEventListener('click', function () {
            loadBootstrap().then(function () {
                openChat();
            }).catch(function (error) {
                showToast(error.message, true);
            });
        });
    });

    document.getElementById('chatClose').addEventListener('click', closeChat);
    document.getElementById('chatToggleSize').addEventListener('click', function () {
        const expanded = app.classList.toggle('chat-fullscreen');
        this.setAttribute('aria-label', expanded ? 'Reduzir chat' : 'Expandir chat');
        this.title = expanded ? 'Reduzir chat' : 'Expandir chat';
        this.querySelector('i').className = expanded
            ? 'fa-solid fa-down-left-and-up-right-to-center'
            : 'fa-solid fa-up-right-and-down-left-from-center';
    });
    document.getElementById('chatSearch').addEventListener('input', renderConversations);

    contactSelect.addEventListener('change', async function () {
        if (!this.value) return;
        try {
            const data = await post(api, { acao: 'iniciar', monitor_id: Number(this.value) });
            await loadBootstrap();
            const conversation = conversations.find(function (item) {
                return Number(item.id) === Number(data.conversa_id);
            });
            if (conversation) selectConversation(conversation);
            this.value = '';
        } catch (error) {
            showToast(error.message, true);
        }
    });

    composeForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        const content = messageInput.value.trim();
        if (!selectedConversation || !content) return;
        messageInput.disabled = true;
        try {
            await post(api, {
                acao: 'enviar',
                conversa_id: selectedConversation.id,
                conteudo: content
            });
            messageInput.value = '';
            await Promise.all([loadMessages(true), loadBootstrap()]);
        } catch (error) {
            showToast(error.message, true);
        } finally {
            messageInput.disabled = false;
            messageInput.focus();
        }
    });

    messageInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            composeForm.requestSubmit();
        }
    });

    document.getElementById('requestAppointment').addEventListener('click', function () {
        const dateInput = document.getElementById('appointmentDate');
        const now = new Date(Date.now() - new Date().getTimezoneOffset() * 60000);
        dateInput.min = now.toISOString().slice(0, 16);
        appointmentDialog.showModal();
    });

    document.querySelectorAll('[data-fechar-agendamento]').forEach(function (button) {
        button.addEventListener('click', function () {
            appointmentDialog.close();
        });
    });

    appointmentForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (!selectedConversation) return;
        const dateInput = document.getElementById('appointmentDate').value;
        const date = new Date(dateInput);
        if (Number.isNaN(date.getTime())) {
            showToast('Informe uma data e horário válidos.', true);
            return;
        }
        const localDate = new Date(date.getTime() - date.getTimezoneOffset() * 60000)
            .toISOString().slice(0, 16);
        try {
            await post(api, {
                acao: 'agendar',
                conversa_id: selectedConversation.id,
                assunto: document.getElementById('appointmentSubject').value.trim(),
                agendado_para: localDate
            });
            appointmentForm.reset();
            appointmentDialog.close();
            showToast('Solicitação enviada. Você receberá uma notificação quando o monitor responder.');
            await Promise.all([loadMessages(false), refreshNotifications(), loadBootstrap()]);
        } catch (error) {
            showToast(error.message, true);
        }
    });

    document.getElementById('markNotificationsRead').addEventListener('click', async function () {
        try {
            await post(notificationsApi, { acao: 'marcar_lidas' });
            await refreshNotifications();
        } catch (error) {
            showToast(error.message, true);
        }
    });

    loadBootstrap().catch(function () {
        chatStatus.textContent = 'Não foi possível conectar';
    });
    refreshNotifications().catch(function () {
        notificationList.replaceChildren();
        const error = document.createElement('p');
        error.className = 'popover-empty';
        error.textContent = 'Não foi possível carregar as notificações.';
        notificationList.appendChild(error);
    });

    window.setInterval(async function () {
        if (refreshing) return;
        refreshing = true;
        try {
            await loadBootstrap();
            await refreshNotifications();
            if (app.classList.contains('chat-open') && selectedConversation) {
                await loadMessages(true);
            }
        } catch (error) {
            chatStatus.textContent = 'Reconectando...';
        } finally {
            refreshing = false;
        }
    }, 5000);
});
