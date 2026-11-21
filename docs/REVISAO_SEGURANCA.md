# Revisão de segurança e fluxos

Revisão concluída em 21 de novembro de 2026 para o MVP local da Beatriz & Kathy Cookies.

## Controles verificados

- Autenticação sem cadastro público, limitação de tentativas, recuperação de senha e segundo fator obrigatório para administradores.
- Perfis admin, editor e viewer aplicados nas rotas, Form Requests e Policies. Somente admin confirma pagamentos; viewer consulta apenas o dashboard.
- CSRF presente em todas as rotas próprias que alteram estado.
- Formulários públicos de contato, avaliação, checkout e recuperação limitados por requisições.
- Dados de clientes, mensagens e pedidos protegidos por autenticação, autorização e respostas sem cache.
- Conteúdo enviado por clientes escapado nas views. Avaliações pendentes ou rejeitadas não aparecem na vitrine.
- Uploads limitados a JPEG, PNG e WebP, com inspeção do tipo real, dimensões, tamanho, nome gerado no servidor e armazenamento fora do document root.
- Preços em centavos, recálculo no servidor e snapshot de nome e preço nos itens do pedido.
- Checkout, pagamento, atendimento, mensagens e avaliações protegidos com transações, bloqueios ou versões conforme o risco.
- Ações administrativas sensíveis auditadas sem senha, token, código de segundo fator, texto de mensagem ou avaliação.
- Cabeçalhos CSP, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy` e `Permissions-Policy` aplicados globalmente. HSTS é enviado em HTTPS de produção.
- Configuração de produção documentada com HTTPS, `APP_DEBUG=false`, cookies seguros, sessão criptografada e segredos no ambiente.

## Validação automatizada

- `php artisan test`: 34 testes e 439 asserções aprovadas.
- `npm run build`: build de produção concluído.
- `composer validate --strict --no-check-publish`: configuração válida.
- `composer audit --locked --no-interaction`: nenhuma vulnerabilidade conhecida encontrada.
- `npm audit`: nenhuma vulnerabilidade conhecida encontrada nas 95 dependências travadas.
- `vendor/bin/pint --test`: arquivos alterados na revisão aprovados.

O teste integrado percorre produto, carrinho, checkout, comprovante, atendimento, pagamento, dashboard, mensagem, avaliação, auditoria e bloqueio dos dados após logout.

## Antes da publicação

- Executar `php artisan migrate --force`, `php artisan config:cache`, `php artisan route:cache` e o build no ambiente de destino.
- Configurar proxy confiável, HTTPS e HSTS conforme a infraestrutura real.
- Usar banco, cache e sessão compartilhados quando houver mais de uma instância.
- Configurar e testar o serviço real de e-mail para recuperação de senha.
- Criar o primeiro administrador de produção por procedimento próprio; não executar `AdminUserSeeder`.
- Fazer inspeção manual responsiva em navegador, incluindo QR Code, upload, checkout, impressão ou cópia do número do pedido e telas administrativas.
- Realizar backup e ensaio de restauração do banco antes de receber pedidos reais.

Os testes de concorrência rejeitam formulários desatualizados e confirmam rollback, mas não substituem um teste de carga com processos MySQL realmente paralelos. A integração com gateway de pagamento continua fora do MVP.
