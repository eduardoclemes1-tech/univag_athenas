# Guia de Contribuição

Obrigado pelo interesse em contribuir com o **UNIVAG Athenas**!

## Como Contribuir

1. **Faça um Fork** do projeto.
2. **Crie uma branch** para a sua funcionalidade ou correção:
   ```bash
   git checkout -b feature/minha-melhoria
   ```
3. **Escreva código limpo e seguro**:
   * Siga os padrões do PSR-12 para código PHP.
   * Utilize `PDO` com *Prepared Statements* para todas as consultas SQL.
   * Valide e sanitize todas as entradas de usuário antes de processamento ou renderização.
   * Mantenha boas práticas de performance (evite subqueries desnecessárias ou polling síncrono frequente).
4. **Faça o commit** das suas alterações com mensagens claras e semânticas:
   ```bash
   git commit -m "feat: adiciona filtro por semestre no catálogo de monitores"
   ```
5. **Faça o Push** para a sua branch:
   ```bash
   git push origin feature/minha-melhoria
   ```
6. **Abra um Pull Request** detalhando as alterações propostas.

---

## Reportando Bugs e Sugestões

Utilize as [Issues do GitHub](https://github.com/eduardoclemes1-tech/univag_athenas/issues) para relatar problemas técnicos ou propor novas funcionalidades através dos templates fornecidos.
