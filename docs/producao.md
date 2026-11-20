# Produção

A área restrita só deve ir ao ar com estes pontos definidos no ambiente do servidor, nunca no repositório.

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY` gerada no servidor e mantida fora do git
- `APP_URL` com `https://`
- Banco, e-mail e qualquer segredo somente em variáveis de ambiente
- `SESSION_SECURE_COOKIE=true` (o código também força o cookie seguro quando `APP_ENV=production`)
- `SESSION_ENCRYPT=true`
- `SESSION_SAME_SITE=lax`
- Document root em `public/`
- HTTPS na frente da aplicação. Se houver proxy, configure os proxies confiáveis antes de publicar
- `MAIL_MAILER` real. O valor `log` é só para desenvolvimento e grava o link de redefinição em `storage/logs`
- Não execute `php artisan db:seed --class=AdminUserSeeder`. Ele recusa qualquer ambiente que não seja `local`
- Não copie `ADMIN_EMAIL` nem `ADMIN_PASSWORD` para o servidor. O primeiro administrador de produção deve ser criado por um procedimento próprio, fora desse seeder
- `storage/` e `bootstrap/cache/` graváveis pelo usuário do PHP. O restante do código não precisa de escrita
- Cadastro público de operadores permanece desligado
- As fotos do catálogo ficam em `storage/app/catalog`, fora do document root, e saem só pela rota de mídia com o tipo da imagem. Não exponha essa pasta e não deixe o servidor executar scripts dentro de `storage`

Os logs de auditoria registram a ação, o operador e o alvo. Eles não recebem senha, segredo de segundo fator, código ou token.

## Carrinho e pedidos

- Execute as migrations antes de liberar o checkout.
- Use sessões no servidor (`database` ou `redis`) e um cache com suporte a locks compartilhado entre todas as instâncias. Não use sessão em cookie. As rotas do carrinho e checkout usam bloqueio de sessão.
- O MVP combina entrega/retirada e pagamento manualmente. Não existe gateway nem cobrança automática; registrar estorno apenas documenta uma devolução já realizada.
- Pedidos e dados de clientes ficam restritos a admin/editor. O comprovante público exige a mesma sessão da compra e não apresenta dados de contato.
- Confirmações de pagamento e alterações de atendimento são auditadas na mesma transação. O registro preserva data e operador da confirmação original mesmo após estorno.

## Dashboard e formulários públicos

- Mensagens e avaliações exigem migrations atualizadas. Dados de contato aparecem somente para admin/editor e as respostas públicas são escapadas ao renderizar.
- As rotas públicas de contato e avaliação têm limitação de cinco envios por minuto. Em produção, use um cache compartilhado para que o limite funcione entre todas as instâncias.
- Avaliações entram como pendentes e precisam de moderação. Alterações de mensagens e avaliações usam versão e bloqueio de banco para rejeitar formulários desatualizados.
- O dashboard calcula faturamento e unidades pela data de confirmação do pagamento, excluindo pedidos cancelados e pagamentos estornados. Mantenha o fuso horário da aplicação alinhado ao horário operacional da loja.
