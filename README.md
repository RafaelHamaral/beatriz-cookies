# Beatriz & Kathy Cookies

Loja de cookies artesanais e doces gourmet. A vitrine mostra categorias, produtos e avaliações aprovadas, permite carrinho, checkout sem cadastro, contato e envio de avaliações. A área restrita gerencia catálogo, pedidos, pagamentos, mensagens e moderação.

O PHP disponível nesta máquina é 8.2. O Laravel 13 exige PHP 8.3, então a aplicação usa Laravel 12, que ainda recebe correções de segurança e roda nesta versão.

## Ambiente local

1. Copie `.env.example` para `.env` se ainda não existir.
2. Preencha o banco MySQL e, só nesta máquina, `ADMIN_EMAIL`, `ADMIN_PASSWORD` e `ADMIN_NAME`.
3. Gere a chave, se o `.env` ainda não tiver uma: `php artisan key:generate`.
4. Crie as tabelas e o administrador:

```bash
php artisan migrate
php artisan db:seed --class=AdminUserSeeder
php artisan serve
```

Como exemplo, a vitrine fica em `https://loja.example.com` e o catálogo em `https://loja.example.com/catalogo`. O login fica em `https://loja.example.com/login`.

O seeder só roda com `APP_ENV=local`, não faz parte do `DatabaseSeeder` e não troca a senha nem o perfil se a conta já existir. A senha é gravada com `Hash::make()`.

As migrations criam ou alteram somente a estrutura das tabelas: não importam, exportam nem incluem dados do banco existente. O `DatabaseSeeder` não cadastra usuários, contatos ou SMTP. Apenas a execução explícita do `AdminUserSeeder` cria o administrador informado nas variáveis locais, sem credenciais fixas no código. Remetente, destinatário, endereço e demais contatos devem ser cadastrados no painel; em uma instalação nova, esses dados ficam vazios. Arquivos `.env` e suas variantes são ignorados pelo Git, com exceção do `.env.example`, que contém apenas campos vazios ou exemplos fictícios. Alterar esses padrões do código não modifica os dados já salvos no banco.

No ambiente local o e-mail de redefinição de senha vai para `storage/logs/laravel.log`.

## Notificações de pedidos e mensagens por e-mail

### Configuração do Outlook pela área restrita

Em **Área restrita → Configurações de e-mail** (`/area-restrita/configuracoes/email`), administradores com dois fatores configuram o remetente, o nome e o destinatário. Como exemplo fictício, use `contato@example.com` para remetente e destinatário e servidor `smtp.example.com`, porta 587 e STARTTLS obrigatório. Esta configuração se aplica às notificações de pedidos e mensagens; a redefinição de senha dos operadores continua usando o mailer do ambiente.

O Outlook.com pessoal exige [OAuth2](https://support.microsoft.com/pt-br/outlook/pop-imap-and-smtp-settings-for-outlook-com). Registre no Microsoft Entra um aplicativo que aceite contas Microsoft pessoais, configure a plataforma **Web**, cadastre exatamente a URL de retorno exibida no painel, habilite a permissão delegada `https://outlook.office.com/SMTP.Send` e crie um segredo de cliente. Configure `APP_URL` com a URL pública HTTPS do site em produção. Salve no painel o ID do aplicativo e o valor do segredo; clique em **Conectar Outlook** e entre com a mesma conta informada como remetente. Não use a senha da caixa de e-mail neste formulário.

A implementação usa o [fluxo de código de autorização da Microsoft](https://learn.microsoft.com/en-us/entra/identity-platform/v2-oauth2-auth-code-flow) com state de uso único, validade de dez minutos, PKCE S256 e escopo `offline_access` para renovar o acesso automaticamente. SMTP autentica exclusivamente por XOAUTH2. Segredo, access token e refresh token são criptografados com `APP_KEY`, ocultos na serialização e não retornam ao formulário nem à sessão de validação. Guarde a chave da aplicação em segurança: ela é necessária para descriptografar essas credenciais. O botão **Enviar dois e-mails de teste** reutiliza o comando abaixo, com dados fictícios e assuntos `[TESTE]`. O aceite SMTP não confirma a entrega na caixa de entrada.

Deixar o segredo em branco mantém o valor salvo. Alterar aplicativo, segredo ou remetente invalida os tokens e exige reconectar; mudar apenas nome ou destinatário preserva a autorização. **Desconectar Outlook** remove os tokens locais e interrompe novos envios, sem apagar pedidos ou mensagens. Para revogação na Microsoft, remova também a permissão do aplicativo na conta Microsoft. Quando o segredo do aplicativo expirar, substitua-o no painel e reconecte. As configurações do painel têm precedência sobre as variáveis SMTP de ambiente depois de salvas e são consultadas a cada envio, inclusive por workers já em execução.

`php artisan test --filter=OutlookMailSettingsTest` cobre permissões, criptografia, ausência de segredos no HTML/old input, callback OAuth, PKCE/state, renovação, desconexão e uso do remetente/destinatário salvos. Os testes não contatam a Microsoft.

### Configuração alternativa por ambiente e operação da fila

Enquanto nenhuma configuração tiver sido salva no painel, o mecanismo anterior por ambiente continua disponível para outros provedores SMTP:

Novos pedidos e mensagens criam, na mesma transação, um registro em `store_notification_deliveries`. Após o commit, o Laravel enfileira um job contendo apenas o ID desse registro. Os e-mails usam o SMTP já disponível no Laravel/Symfony, com assuntos `Novo pedido #ID — Site Beatriz` e `Nova mensagem — Site Beatriz`, dados de contato, itens/preços do pedido ou conteúdo da mensagem. O remetente vem de `MAIL_FROM_ADDRESS` e deve ser autorizado pelo provedor; o e-mail válido do visitante fica somente no Reply-To.

Configure no `.env`, sem versionar segredos:

- `STORE_NOTIFICATIONS_TO=contato@example.com` e `STORE_NOTIFICATIONS_MAILER=smtp`.
- `MAIL_HOST=smtp.example.com`, `MAIL_PORT`, `MAIL_USERNAME=usuario@example.com`, `MAIL_PASSWORD=****`: exemplos fictícios das credenciais do provedor de envio, que não precisa ser Outlook. Use `MAIL_SCHEME=smtp` e porta 587 para STARTTLS, ou `MAIL_SCHEME=smtps` e porta 465 para TLS implícito, conforme o provedor. TLS e validação do certificado são obrigatórios; não desabilite `require_tls`/`verify_peer` via `MAIL_URL`.
- `MAIL_FROM_ADDRESS`: caixa/domínio autorizado pelo provedor; `MAIL_FROM_NAME`: nome da loja. Configure a autenticação de domínio exigida pelo provedor (SPF/DKIM). O endereço do visitante nunca substitui o remetente.
- `MAIL_TIMEOUT=20`, `QUEUE_CONNECTION=database`, `DB_QUEUE_RETRY_AFTER=90` (sempre maior que o timeout de 45 segundos do job). As notificações usam explicitamente SMTP, mesmo se `MAIL_MAILER=log` estiver configurado para outros e-mails; transportes `log`, `array` e `failover` são recusados para impedir gravação de dados dos visitantes nos logs.

Após configurar, execute `php artisan migrate` e `php artisan config:cache`. Mantenha `php artisan queue:work --sleep=3 --timeout=45` em execução por Supervisor ou serviço equivalente e reinicie os workers com `php artisan queue:restart` após alterações. Em hospedagem compartilhada sem processo persistente, agende `php artisan queue:work --stop-when-empty --timeout=45 --max-time=50` a cada minuto, sem execuções concorrentes. Ative também o cron do Laravel (`php artisan schedule:run` a cada minuto); ele recupera registros que não chegaram à fila. A recuperação também pode ser acionada com `php artisan notifications:dispatch-pending`.

Falhas de SMTP preservam pedido/mensagem, não marcam o envio como concluído e geram log apenas com o ID interno da notificação. São feitas até três tentativas, com intervalos de 60 e 300 segundos; após esgotar, use `php artisan queue:failed` e `php artisan queue:retry UUID` após corrigir a configuração. A exceção salva em `failed_jobs` é sanitizada, sem resposta SMTP, credenciais ou dados pessoais. Há um registro único por origem, bloqueio no banco durante o envio e verificação de `sent_at` para evitar duplicações por repetição do checkout ou reprocessamento. Como em qualquer envio SMTP, uma queda após o provedor aceitar o e-mail e antes de salvar `sent_at` pode deixar o resultado incerto; confirme com o provedor antes de reenviar esse caso.

Para testar o provedor após configurar as credenciais, execute `php artisan notifications:test-smtp`: ele envia exatamente um e-mail por fluxo, com `[TESTE]` no assunto e dados fictícios, sem criar pedidos ou mensagens. Sucesso significa que o provedor aceitou o envio, não que o Outlook entregou na caixa de entrada; confirme o recebimento (incluindo lixo eletrônico) no Outlook. `php artisan test --filter=StoreNotificationsTest` cobre os dois fluxos, conteúdo/Reply-To, commit/rollback, reenvio sem duplicação, recuperação da fila, falha SMTP simulada e proteção dos logs.

## Testes

```bash
php artisan test
```

Os testes usam SQLite em memória e senhas geradas pela factory. Eles não usam a senha do `.env`.

## Estado atual

As cinco etapas do MVP foram implementadas. Antes de publicar, execute a validação manual em navegador e siga `docs/producao.md`. O resultado técnico da última revisão está em `docs/REVISAO_SEGURANCA.md`.

## Etapa 3 — carrinho e pedidos

Após atualizar o código, execute `php artisan migrate` e `php artisan serve`.

1. Como admin/editor, cadastre uma categoria ativa e um produto ativo e disponível com preço.
2. Sem login, abra `/catalogo`, entre no produto, defina a quantidade e adicione ao carrinho.
3. Em `/carrinho`, revise os itens e siga para `/checkout`. Preencha nome, e-mail e telefone. Entrega ou retirada e pagamento serão combinados pela loja; o total é apenas dos produtos.
4. Envie o pedido e confira o número e os status pendentes. A confirmação só é acessível na sessão que fez a compra.
5. Entre em `/login`, abra `/area-restrita/pedidos` e filtre por número, atendimento ou pagamento.
6. Como editor, avance o atendimento: confirmação, preparo, pronto e concluído. A conclusão exige pagamento registrado. Também é possível cancelar antes da conclusão.
7. Como administrador, registre o recebimento do pagamento. Confira a data e o operador. Se necessário, registre um estorno depois de efetivamente devolver o valor. Cancelar um pedido não faz o estorno automaticamente.

Pedidos preservam nome e preço unitário dos itens. O checkout recalcula tudo no servidor e exige nova revisão se o preço mudar. Uma chave única e bloqueio da sessão protegem contra reenvio duplicado. As alterações administrativas usam transação, bloqueio da linha e versão do pedido; formulários antigos são rejeitados. Pagamentos não podem ser confirmados duas vezes nem registrados em pedidos cancelados ou estornados.

Com um WhatsApp válido cadastrado em **Contato da loja** (código do país, DDD e número, somente dígitos), o checkout mostra **Enviar pedido pelo WhatsApp**. Após salvar o pedido e registrar a notificação por e-mail, a confirmação abre a conversa automaticamente, uma única vez, com uma mensagem codificada a partir dos dados salvos: número do pedido, nome, produtos, quantidades, valores por unidade, subtotais, total e observações preenchidas. A mensagem só é enviada quando o cliente tocar em **Enviar** no WhatsApp. O botão **Abrir WhatsApp** na confirmação permite reabrir a conversa sem criar outro pedido. Sem número válido, o pedido é registrado normalmente e a confirmação informa a indisponibilidade do WhatsApp. Não são acrescentados campos de variações, entrega, retirada, endereço, frete ou forma de pagamento: o checkout atual não cadastra essas informações, que continuam sendo combinadas com a loja. O redirecionamento usa HTML, sem scripts ou alterações na política de segurança.

`php artisan test --filter=CheckoutWhatsAppTest` verifica múltiplos itens, preços salvos, caracteres especiais, destinos ausentes/inválidos, privacidade da confirmação, abertura automática única, reabertura e preservação da notificação por e-mail.

`php artisan test` verifica cálculos, preços alterados, disponibilidade, checkout duplicado, permissões, filtros, rollback, pagamento duplicado e versões desatualizadas. Os testes usam SQLite em memória; não simulam dois processos concorrentes no MySQL.

## Etapa 4 — dashboard, mensagens e avaliações

- `/area-restrita` mostra faturamento, unidades, pedidos pendentes, movimento diário e ranking de produtos com um único filtro de período.
- Faturamento e unidades consideram apenas pedidos pagos pela data da confirmação. Pedidos cancelados ou estornados ficam fora dos números.
- `/contato` recebe mensagens públicas. Admin e editor acompanham leitura e atendimento em `/area-restrita/mensagens`.
- `/avaliar` recebe notas de 1 a 5. Avaliações começam pendentes e só aparecem na página inicial depois da aprovação em `/area-restrita/avaliacoes`.
- Badges na navegação informam pedidos aguardando confirmação, mensagens não lidas e avaliações pendentes.
- Viewer consulta somente o dashboard. Admin e editor operam mensagens e avaliações.

## Horário de funcionamento

Execute `php artisan migrate` para adicionar a coluna JSON `opening_hours` às configurações de contato. Administradores com dois fatores configurados e editores acessam **Horário de funcionamento** no menu do painel, também disponível em **Contato da loja**. Para cada um dos sete dias, escolha **Fechado** ou **Aberto nos intervalos abaixo** e informe um intervalo por linha, como `08:00–12:00` e `13:00–18:00`. Os horários usam formato de 24 horas e devem começar e terminar no mesmo dia; intervalos sobrepostos são rejeitados. Intervalos consecutivos são permitidos.

Os horários ficam ocultos até o primeiro salvamento. Depois, aparecem na página de contato e no rodapé, com todos os dias e a indicação **Fechado** quando aplicável. As alterações são registradas na auditoria e não modificam os demais dados de contato. `php artisan test --filter=OpeningHoursTest` verifica cadastro, edição, pausas, dias fechados, formatos inválidos, sobreposições, persistência, exibição pública e permissões do painel.

## Aviso de cookies

Auditoria do código: o Laravel utiliza o cookie de sessão (nome configurado em `config/session.php`) para login, autenticação em dois fatores, carrinho e checkout; `XSRF-TOKEN` protege requisições contra CSRF. Não há scripts de terceiros carregados, uso de localStorage/sessionStorage ou cookies opcionais. Os links para redes sociais e WhatsApp são links externos, sem embeds. Não existe uma rota de Política de Privacidade.

O aviso informativo aparece no layout público até a confirmação em “Entendi”. O cookie `beatriz_cookie_notice` guarda somente a versão confirmada (`v1`) por 180 dias, com HttpOnly e SameSite=Lax, e Secure em HTTPS. “Preferências de cookies” no rodapé reabre o aviso. Não há categorias opcionais para aceitar, recusar ou revogar. Cookies de sessão e login não são removidos. A implementação usa formulário POST com CSRF e mantém a política `script-src 'none'`.

`php artisan test --filter=CookieNoticeTest` verifica primeira visita, confirmação, validade da persistência, navegação com confirmação salva, reabertura pelo rodapé e preservação de autenticação/carrinho. Para a revisão visual, conferir 320px/390px e desktop, zoom, Tab/Shift+Tab e Enter no link do rodapé e no botão; o banner tem rolagem própria em telas baixas e espaço reservado ao fim da página.
