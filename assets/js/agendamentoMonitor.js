document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('monitorAppointmentForm');
    const dialog = document.getElementById('monitorAppointmentDialog');
    const openButton = document.getElementById('btnScheduleMonitor');
    if (!form || !dialog || !openButton) return;

    const api = form.dataset.api;
    const csrfToken = form.dataset.csrf;
    const studentSelect = document.getElementById('monitorAppointmentStudent');
    const dateInput = document.getElementById('monitorAppointmentDate');
    const feedback = document.getElementById('monitorAppointmentFeedback');
    const submitButton = form.querySelector('button[type="submit"]');
    let studentsLoaded = false;

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

    function setDateLimits() {
        const now = new Date();
        const localNow = new Date(now.getTime() - now.getTimezoneOffset() * 60000);
        dateInput.min = localNow.toISOString().slice(0, 16);
        localNow.setDate(localNow.getDate() + 90);
        dateInput.max = localNow.toISOString().slice(0, 16);
    }

    async function loadStudents() {
        if (studentsLoaded) return;
        studentSelect.replaceChildren(new Option('Carregando alunos...', ''));
        const response = await fetch(api + '?acao=alunos');
        const payload = await response.json();
        if (!response.ok) {
            throw new Error(payload.erro || 'Não foi possível carregar os alunos.');
        }

        studentSelect.replaceChildren(new Option('Selecione um aluno', ''));
        payload.alunos.forEach(function (student) {
            studentSelect.add(new Option(student.nome + ' — ' + student.email, student.id));
        });
        if (!payload.alunos.length) {
            studentSelect.replaceChildren(new Option('Nenhum aluno cadastrado', ''));
        }
        studentsLoaded = true;
    }

    openButton.addEventListener('click', function () {
        feedback.textContent = '';
        feedback.classList.remove('monitor-appointment-error');
        setDateLimits();
        dialog.showModal();
        loadStudents().catch(function (error) {
            studentSelect.replaceChildren(new Option('Falha ao carregar alunos', ''));
            feedback.textContent = error.message;
            feedback.classList.add('monitor-appointment-error');
        });
    });

    document.querySelectorAll('[data-fechar-monitor-agendamento]').forEach(function (button) {
        button.addEventListener('click', function () {
            dialog.close();
        });
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        feedback.textContent = '';
        feedback.classList.remove('monitor-appointment-error');
        submitButton.disabled = true;

        try {
            const response = await fetch(api, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    acao: 'agendar_monitor',
                    aluno_id: Number(studentSelect.value),
                    assunto: document.getElementById('monitorAppointmentSubject').value.trim(),
                    agendado_para: dateInput.value,
                    duracao_minutos: Number(document.getElementById('monitorAppointmentDuration').value),
                    link_zoom: document.getElementById('monitorAppointmentZoom').value.trim()
                })
            });
            const payload = await response.json();
            if (!response.ok) {
                throw new Error(payload.erro || 'Não foi possível criar o agendamento.');
            }

            form.reset();
            dialog.close();
            window.dispatchEvent(new CustomEvent('athenas:appointment-created'));
            showToast('Monitoria agendada. O aluno receberá uma mensagem e uma notificação.', false);
        } catch (error) {
            feedback.textContent = error.message;
            feedback.classList.add('monitor-appointment-error');
            showToast(error.message, true);
        } finally {
            submitButton.disabled = false;
        }
    });
});
