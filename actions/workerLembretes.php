<?php
require_once __DIR__ . '/../config/conexao.php';

function enviarLembretes(PDO $pdo): int
{
    $agendamentos = $pdo->query(
        "SELECT a.id, a.assunto, a.agendado_para, a.aluno_id, a.monitor_id,
                aluno.notificar_agendamentos AS aluno_notifica,
                monitor.notificar_agendamentos AS monitor_notifica
         FROM agendamentos a
         JOIN usuarios aluno ON aluno.id = a.aluno_id
         JOIN usuarios monitor ON monitor.id = a.monitor_id
         WHERE a.status = 'confirmado'
           AND a.agendado_para > CURRENT_TIMESTAMP
           AND a.agendado_para <= DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 24 HOUR)
         ORDER BY a.agendado_para
         LIMIT 200"
    )->fetchAll();

    $enviados = 0;
    foreach ($agendamentos as $agendamento) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT IGNORE INTO lembretes_agendamentos (agendamento_id, usuario_id)
                 VALUES (?, ?)'
            );
            foreach ([
                [(int) $agendamento['aluno_id'], (bool) $agendamento['aluno_notifica']],
                [(int) $agendamento['monitor_id'], (bool) $agendamento['monitor_notifica']],
            ] as [$usuarioId, $notificar]) {
                $stmt->execute([(int) $agendamento['id'], $usuarioId]);
                if ($stmt->rowCount() !== 1) {
                    continue;
                }

                if ($notificar) {
                    $notificacao = $pdo->prepare(
                        'INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem, agendamento_id)
                         VALUES (?, ?, ?, ?, ?)'
                    );
                    $notificacao->execute([
                        $usuarioId,
                        'agendamento',
                        'Lembrete de monitoria',
                        'Sua monitoria "' . $agendamento['assunto'] . '" está agendada para '
                            . date('d/m/Y às H:i', strtotime($agendamento['agendado_para'])) . '.',
                        (int) $agendamento['id'],
                    ]);
                    $enviados++;
                }
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    return $enviados;
}

$executarUmaVez = in_array('--once', $argv, true);
do {
    try {
        $quantidade = enviarLembretes($pdo);
        if ($quantidade > 0) {
            error_log("Lembretes de monitoria criados: {$quantidade}");
        }
    } catch (Throwable $e) {
        error_log('Falha ao processar lembretes de monitoria: ' . $e->getMessage());
        if ($executarUmaVez) {
            exit(1);
        }
    }

    if (!$executarUmaVez) {
        sleep(300);
    }
} while (!$executarUmaVez);
