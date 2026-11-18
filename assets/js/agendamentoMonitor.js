document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('monitorAppointmentForm');
    const dialog = document.getElementById('monitorAppointmentDialog');
    const openButton = document.getElementById('btnScheduleMonitor');
    if (!form || !dialog || !openButton) return;

    const api = form.dataset.api;
    const csrfToken = form.dataset.csrf;
    const studentSelect = document.getElementById('monitorAppointmentStudent');
    const dateInput = document.getElementById('monitorAppointmentDate');
    const durationSelect = document.getElementById('monitorAppointmentDuration');
    const timeSelect = document.getElementById('monitorAppointmentTime');
    const hint = document.getElementById('monitorAppointmentHint');
    const feedback = document.getElementById('monitorAppointmentFeedback');
    const submitButton = form.querySelector('button[type="submit"]');
    let studentsLoaded = false;
    let availabilityRequestId = 0;

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
        dateInput.min = localNow.toISOString().slice(0, 10);
        localNow.setDate(localNow.getDate() + 90);
        dateInput.max = localNow.toISOString().slice(0, 10);
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

    async function loadAvailableSlots() {
        const requestId = ++availabilityRequestId;
        timeSelect.disabled = true;
        timeSelect.replaceChildren(new Option('Consultando horários...', ''));
        if (!dateInput.value) {
            timeSelect.replaceChildren(new Option('Escolha uma data primeiro', ''));
            if (hint) {
                hint.textContent = 'Escolha a data para consultar seus horários livres cadastrados no perfil.';
            }
            return;
        }

        if (hint) {
            hint.textContent = 'Consultando seus horários livres...';
        }
        try {
            const params = new URLSearchParams({
                acao: 'horarios_disponiveis',
                data: dateInput.value,
                duracao: durationSelect.value,
                aluno_id: studentSelect.value || '0'
            });
            const response = await fetch(api + '?' + params.toString());
            const payload = await response.json();
            if (!response.ok) {
                throw new Error(payload.erro || 'Não foi possível consultar os horários.');
            }
            if (requestId !== availabilityRequestId) return;

            const horarios = payload.horarios || [];
            timeSelect.replaceChildren(new Option(
                horarios.length ? 'Selecione um horário disponível' : 'Nenhum horário livre nesta data',
                ''
            ));
            horarios.forEach(function (horario) {
                timeSelect.add(new Option(horario, horario));
            });
            timeSelect.disabled = horarios.length === 0;
            if (hint) {
                hint.textContent = horarios.length
                    ? 'Horários livres filtrados automaticamente conforme sua disponibilidade e agenda.'
                    : 'Você não possui horários livres cadastrados para esse dia/duração. Tente outra data ou ajuste sua disponibilidade no Perfil.';
            }
        } catch (error) {
            if (requestId !== availabilityRequestId) return;
            timeSelect.replaceChildren(new Option('Não foi possível carregar', ''));
            if (hint) {
                hint.textContent = error.message;
            }
        }
    }

    async function openModalForStudent(alunoId) {
        feedback.textContent = '';
        feedback.classList.remove('monitor-appointment-error');
        setDateLimits();
        dateInput.value = '';
        timeSelect.disabled = true;
        timeSelect.replaceChildren(new Option('Escolha uma data primeiro', ''));
        if (hint) {
            hint.textContent = 'Escolha a data para consultar seus horários livres cadastrados no perfil.';
        }
        const searchDropdown = document.getElementById('searchDropdown');
        if (searchDropdown) searchDropdown.classList.remove('active');
        dialog.showModal();
        try {
            await loadStudents();
            if (alunoId) {
                studentSelect.value = String(alunoId);
            }
        } catch (error) {
            studentSelect.replaceChildren(new Option('Falha ao carregar alunos', ''));
            feedback.textContent = error.message;
            feedback.classList.add('monitor-appointment-error');
        }
    }

    openButton.addEventListener('click', function () {
        openModalForStudent(null);
    });

    document.addEventListener('click', function (event) {
        const btnAluno = event.target.closest('[data-agendar-com-aluno]');
        if (btnAluno) {
            event.preventDefault();
            openModalForStudent(btnAluno.dataset.agendarComAluno);
        }
    });

    dateInput.addEventListener('change', loadAvailableSlots);
    durationSelect.addEventListener('change', loadAvailableSlots);
    studentSelect.addEventListener('change', function () {
        if (dateInput.value) {
            loadAvailableSlots();
        }
    });

    document.querySelectorAll('[data-fechar-monitor-agendamento]').forEach(function (button) {
        button.addEventListener('click', function () {
            dialog.close();
        });
    });

    dialog.addEventListener('click', function (event) {
        if (event.target === dialog) {
            dialog.close();
        }
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        feedback.textContent = '';
        feedback.classList.remove('monitor-appointment-error');

        if (!dateInput.value || !timeSelect.value) {
            feedback.textContent = 'Selecione uma data e um horário disponível.';
            feedback.classList.add('monitor-appointment-error');
            return;
        }

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
                    agendado_para: dateInput.value + 'T' + timeSelect.value,
                    duracao_minutos: Number(durationSelect.value),
                    meeting_url: document.getElementById('monitorAppointmentUrl').value.trim()
                })
            });
            const payload = await response.json();
            if (!response.ok) {
                throw new Error(payload.erro || 'Não foi possível criar o agendamento.');
            }

            form.reset();
            timeSelect.disabled = true;
            timeSelect.replaceChildren(new Option('Escolha uma data primeiro', ''));
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
