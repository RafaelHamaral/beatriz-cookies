# Mapa do projeto Beatriz

Inventário das classes PHP próprias e seus pontos de uso. Inclui classes de testes e dados auxiliares; traits e enum estão identificados. Não inclui classes CSS, dependências nem arquivos gerados. Nomes repetidos usam o prefixo do namespace. Caminhos relativos à raiz.

## Classes

| Classe | Arquivo | O que faz | Consulte para alterar |
| --- | --- | --- | --- |
| `Controller` | `app/Http/Controllers/Controller.php` | Oferece autorização aos controladores. | Base dos controladores; policies. |
| `HomeController` | `app/Http/Controllers/HomeController.php` | Exibe produtos visíveis e avaliações aprovadas. | Página inicial; `resources/views/home.blade.php`. |
| `CatalogController` | `app/Http/Controllers/CatalogController.php` | Lista catálogo e produtos por categoria ativa. | Catálogo; `resources/views/catalog/`. |
| `ProductPageController` | `app/Http/Controllers/ProductPageController.php` | Exibe detalhes de produto visível. | Página do produto; `resources/views/catalog/product.blade.php`. |
| `ProductImageController` | `app/Http/Controllers/ProductImageController.php` | Entrega imagens permitidas pelo identificador público. | Entrega de mídia; disco em `config/filesystems.php`. |
| `ContactController` | `app/Http/Controllers/ContactController.php` | Recebe e salva mensagens de contato. | Formulário público; `StoreContactMessageRequest`. |
| `ReviewController` | `app/Http/Controllers/ReviewController.php` | Recebe avaliações com status pendente. | Avaliação pública; `StoreReviewRequest`. |
| `CartController` | `app/Http/Controllers/CartController.php` | Exibe e atualiza o carrinho na sessão. | Carrinho; `CartRequest` e `Cart`. |
| `CheckoutController` | `app/Http/Controllers/CheckoutController.php` | Revalida preços e cria pedido e itens em transação. | Checkout, repetição de envio e comprovante; `Cart` e `CheckoutRequest`. |
| `Auth\LoginController` | `app/Http/Controllers/Auth/LoginController.php` | Coordena login, sessão, segundo fator e logout. | Login; senha em `LoginRequest`; papéis em `User` e policies. |
| `Auth\PasswordResetController` | `app/Http/Controllers/Auth/PasswordResetController.php` | Envia link e executa redefinição de senha. | Recuperação; `NewPasswordRequest`, `User` e `ResetOperatorPassword`. |
| `Auth\TwoFactorChallengeController` | `app/Http/Controllers/Auth/TwoFactorChallengeController.php` | Confirma segundo fator antes de concluir login. | Desafio e recuperação; `TwoFactorAuthentication`. |
| `Auth\TwoFactorSetupController` | `app/Http/Controllers/Auth/TwoFactorSetupController.php` | Configura autenticador e exibe códigos de recuperação. | Ativação de 2FA; `ConfirmTwoFactorRequest` e `TwoFactorAuthentication`. |
| `Admin\CategoryController` | `app/Http/Controllers/Admin/CategoryController.php` | Cria, edita, lista e ativa categorias. | Gestão de categorias; requests e `CategoryPolicy`. |
| `Admin\ProductController` | `app/Http/Controllers/Admin/ProductController.php` | Gerencia produtos, imagens e capa. | Gestão de produtos; `ProductImageStorage` e `ProductPolicy`. |
| `Admin\DashboardController` | `app/Http/Controllers/Admin/DashboardController.php` | Valida período e apresenta métricas. | Painel; `DashboardMetrics` e `resources/views/admin/dashboard.blade.php`. |
| `Admin\MessageController` | `app/Http/Controllers/Admin/MessageController.php` | Lista mensagens e registra leitura e atendimento. | Mensagens internas; `MessageStatusRequest` e `ContactMessagePolicy`. |
| `Admin\ReviewController` | `app/Http/Controllers/Admin/ReviewController.php` | Lista e modera avaliações com controle de versão. | Moderação; `ModerateReviewRequest` e `ReviewPolicy`. |
| `Admin\OrderController` | `app/Http/Controllers/Admin/OrderController.php` | Lista pedidos e altera atendimento e pagamento. | Transições e concorrência; `OrderUpdateRequest` e `OrderPolicy`. |
| `Admin\UserController` | `app/Http/Controllers/Admin/UserController.php` | Cria, lista, exclui e altera papéis de operadores. | Operadores; `StoreUserRequest`, `UpdateUserRoleRequest` e `UserPolicy`. |
| `AuditLog` | `app/Models/AuditLog.php` | Persiste ações, autor, alvo e propriedades. | Auditoria gravada por `AuditLogger`. |
| `Category` | `app/Models/Category.php` | Mapeia categorias, produtos e slug de rota. | Catálogo público e `Admin\CategoryController`. |
| `Product` | `app/Models/Product.php` | Mapeia produtos, relações, visibilidade e preço formatado. | Catálogo, carrinho e gestão; `HasUniqueSlug` e `Money`. |
| `Models\ProductImage` | `app/Models/ProductImage.php` | Mapeia imagens, capa e identificador público. | `Admin\ProductController` e `ProductImageController`. |
| `User` | `app/Models/User.php` | Mapeia operadores, hash de senha, papel e segredos cifrados. | Credenciais e papéis; `LoginRequest`, `UserRole` e policies. |
| `ContactMessage` | `app/Models/ContactMessage.php` | Mapeia mensagem, versão e operador responsável. | `ContactController`, `Admin\MessageController` e notificações do painel. |
| `Review` | `app/Models/Review.php` | Mapeia avaliação, status, versão e moderador. | Página inicial e controladores de avaliações. |
| `Order` | `app/Models/Order.php` | Mapeia pedido, estados, itens e responsável pelo pagamento. | Checkout, gestão de pedidos e `DashboardMetrics`. |
| `OrderItem` | `app/Models/OrderItem.php` | Guarda nome, preço e quantidade no momento do pedido. | Itens criados por `CheckoutController`; ranking em `DashboardMetrics`. |
| `LoginRequest` | `app/Http/Requests/Auth/LoginRequest.php` | Valida credenciais, confere hash e limita tentativas. | Verificação da senha usada por `Auth\LoginController`. |
| `ForgotPasswordRequest` | `app/Http/Requests/Auth/ForgotPasswordRequest.php` | Valida e-mail para recuperação de senha. | Solicitação usada por `Auth\PasswordResetController`. |
| `NewPasswordRequest` | `app/Http/Requests/Auth/NewPasswordRequest.php` | Valida token, e-mail e nova senha confirmada. | Redefinição; limites definidos em `AppServiceProvider`. |
| `ConfirmTwoFactorRequest` | `app/Http/Requests/Auth/ConfirmTwoFactorRequest.php` | Autoriza administrador e valida código de ativação. | `Auth\TwoFactorSetupController`; chave de limitação. |
| `TwoFactorChallengeRequest` | `app/Http/Requests/Auth/TwoFactorChallengeRequest.php` | Exige sessão pendente e valida código de desafio. | `Auth\TwoFactorChallengeController`; chave de limitação. |
| `ActivationRequest` | `app/Http/Requests/Admin/ActivationRequest.php` | Autoriza atualização e valida ativação. | Ativação em controladores de produtos e categorias. |
| `StoreCategoryRequest` | `app/Http/Requests/Admin/StoreCategoryRequest.php` | Autoriza criação e valida dados de categoria. | `Admin\CategoryController::store`; regras herdadas na edição. |
| `UpdateCategoryRequest` | `app/Http/Requests/Admin/UpdateCategoryRequest.php` | Autoriza edição e herda validação de categoria. | `Admin\CategoryController::update`. |
| `StoreProductRequest` | `app/Http/Requests/Admin/StoreProductRequest.php` | Autoriza criação e valida produto, preço, imagens e capa. | `Admin\ProductController::store`; `Money` e `Rules\ProductImage`. |
| `UpdateProductRequest` | `app/Http/Requests/Admin/UpdateProductRequest.php` | Autoriza edição e valida preço, fotos mantidas e capa. | `Admin\ProductController::update`. |
| `StoreUserRequest` | `app/Http/Requests/Admin/StoreUserRequest.php` | Autoriza criação e valida operador, senha e papel. | `Admin\UserController::store`; senha em `AppServiceProvider`. |
| `UpdateUserRoleRequest` | `app/Http/Requests/Admin/UpdateUserRoleRequest.php` | Autoriza alteração e valida papel do operador. | `Admin\UserController::update`; `UserPolicy` e `UserRole`. |
| `MessageStatusRequest` | `app/Http/Requests/Admin/MessageStatusRequest.php` | Autoriza atendimento e valida status e versão. | `Admin\MessageController::update`. |
| `ModerateReviewRequest` | `app/Http/Requests/Admin/ModerateReviewRequest.php` | Autoriza moderação e valida status e versão. | `Admin\ReviewController::update`. |
| `CartRequest` | `app/Http/Requests/Orders/CartRequest.php` | Valida quantidade entre zero e 99. | `CartController::update`; zero remove o item. |
| `CheckoutRequest` | `app/Http/Requests/Orders/CheckoutRequest.php` | Valida chave de checkout e dados do cliente. | `CheckoutController::store`. |
| `OrderUpdateRequest` | `app/Http/Requests/Orders/OrderUpdateRequest.php` | Autoriza ação e valida status e versão do pedido. | `Admin\OrderController::update`; pagamento exige `OrderPolicy::payment`. |
| `StoreContactMessageRequest` | `app/Http/Requests/StoreContactMessageRequest.php` | Valida remetente, contato, assunto e mensagem. | `ContactController::store`. |
| `StoreReviewRequest` | `app/Http/Requests/StoreReviewRequest.php` | Valida nome, comentário e nota de um a cinco. | `ReviewController::store`. |
| `HasUniqueSlug` | `app/Models/Concerns/HasUniqueSlug.php` | Trait que gera slug disponível com sufixo. | `Category::makeSlug` e `Product::makeSlug` na criação. |
| `ThrottlesAttempts` | `app/Http/Requests/Concerns/ThrottlesAttempts.php` | Trait que consulta, incrementa e limpa tentativas. | `LoginRequest` e controladores de 2FA. |
| `UserRole` | `app/Enums/UserRole.php` | Enum que define admin, editor, viewer e rótulos. | `User`, factories, requests e gestão de operadores. |
| `AuditLogger` | `app/Services/AuditLogger.php` | Registra ações removendo propriedades sensíveis. | Chamadas nos controladores administrativos e de segurança. |
| `Cart` | `app/Services/Cart.php` | Valida itens disponíveis e calcula totais em centavos. | `CartController` e `CheckoutController`; bloqueios no checkout. |
| `DashboardMetrics` | `app/Services/DashboardMetrics.php` | Calcula receita, unidades, pendências, ranking e série diária. | `Admin\DashboardController`; período e critérios financeiros. |
| `ProductImageStorage` | `app/Services/ProductImageStorage.php` | Inspeciona, processa, guarda e remove imagens. | `Admin\ProductController` e `Rules\ProductImage`; limites em `config/catalog.php`. |
| `TwoFactorAuthentication` | `app/Services/TwoFactorAuthentication.php` | Gera e valida TOTP, QR e códigos de recuperação. | Controladores de 2FA e auxiliar em `tests/TestCase.php`. |
| `Money` | `app/Support/Money.php` | Converte preço em centavos e formata reais. | Requests de produtos, `Product` e views de pedidos e painel. |
| `Rules\ProductImage` | `app/Rules/ProductImage.php` | Valida upload usando inspeção do armazenamento. | `StoreProductRequest` e `UpdateProductRequest`. |
| `InvalidProductImage` | `app/Exceptions/InvalidProductImage.php` | Representa falha de leitura ou processamento de imagem. | Lançada em `ProductImageStorage`; tratada em `Rules\ProductImage`. |
| `ResetOperatorPassword` | `app/Notifications/ResetOperatorPassword.php` | Monta e-mail com link de redefinição de senha. | `User::sendPasswordResetNotification`. |
| `AppServiceProvider` | `app/Providers/AppServiceProvider.php` | Define limites de senha e cookie seguro em produção. | Registro em `bootstrap/providers.php`; criação e redefinição de senhas. |
| `EnsureUserHasRole` | `app/Http/Middleware/EnsureUserHasRole.php` | Restringe acesso aos papéis aceitos pela rota. | Alias `role` em `bootstrap/app.php`; uso em `routes/web.php`. |
| `EnsureAdminTwoFactor` | `app/Http/Middleware/EnsureAdminTwoFactor.php` | Exige configuração de 2FA de administradores. | Alias `admin.2fa`; grupos restritos em `routes/web.php`. |
| `PreventSensitiveCaching` | `app/Http/Middleware/PreventSensitiveCaching.php` | Acrescenta cabeçalhos para impedir cache. | Alias `no.store`; autenticação, formulários com dados pessoais, área restrita e pedidos. |
| `SecurityHeaders` | `app/Http/Middleware/SecurityHeaders.php` | Aplica CSP, proteção contra iframe e sniffing, política de referência, permissões e HSTS em produção HTTPS. | Middleware global registrado em `bootstrap/app.php`. |
| `ShareRestrictedNotifications` | `app/Http/Middleware/ShareRestrictedNotifications.php` | Compartilha contagens de pedidos, mensagens e avaliações. | Alias `restricted.notifications`; layout restrito e painel. |
| `CategoryPolicy` | `app/Policies/CategoryPolicy.php` | Autoriza gestão de categorias para admin e editor. | Controlador e requests de categorias. |
| `ProductPolicy` | `app/Policies/ProductPolicy.php` | Autoriza gestão de produtos para admin e editor. | Controlador e requests de produtos. |
| `ContactMessagePolicy` | `app/Policies/ContactMessagePolicy.php` | Autoriza leitura e atendimento para admin e editor. | Controlador de mensagens e `MessageStatusRequest`. |
| `ReviewPolicy` | `app/Policies/ReviewPolicy.php` | Autoriza consulta e moderação para admin e editor. | Controlador administrativo e `ModerateReviewRequest`. |
| `OrderPolicy` | `app/Policies/OrderPolicy.php` | Autoriza atendimento e reserva pagamentos ao admin. | Controlador de pedidos, `OrderUpdateRequest` e `@can` na view. |
| `UserPolicy` | `app/Policies/UserPolicy.php` | Autoriza gestão por admin e protege a própria conta e último admin. | Controlador de usuários e requests de criação e papel. |
| `CategoryFactory` | `database/factories/CategoryFactory.php` | Gera categorias ativas ou inativas. | Dados usados por `factory()` nos testes de funcionalidades. |
| `ProductFactory` | `database/factories/ProductFactory.php` | Gera produtos com categoria e estados de disponibilidade. | Dados usados por `factory()` nos testes de funcionalidades. |
| `UserFactory` | `database/factories/UserFactory.php` | Gera operadores com senha de teste e estados de papel. | Dados usados por `factory()` nos testes de funcionalidades. |
| `AdminUserSeeder` | `database/seeders/AdminUserSeeder.php` | Cria administrador local com credenciais do ambiente. | Provisionamento explícito; preserva conta existente; `AdminUserSeederTest`. |
| `DatabaseSeeder` | `database/seeders/DatabaseSeeder.php` | Mantém seeding padrão sem criar administrador. | Entrada de `db:seed`; `AdminUserSeeder` é executado separadamente. |
| `TestCase` | `tests/TestCase.php` | Fornece base Laravel e auxiliar de confirmação de 2FA. | Suporte à suíte. |
| `AuthenticationAndAccessTest` | `tests/Feature/AuthenticationAndAccessTest.php` | Verifica login, recuperação, 2FA e permissões. | Validação pelo PHPUnit; `phpunit.xml`. |
| `CatalogTest` | `tests/Feature/CatalogTest.php` | Verifica visibilidade, preços, uploads e acesso ao catálogo. | Validação pelo PHPUnit; `phpunit.xml`. |
| `DashboardAndFeedbackTest` | `tests/Feature/DashboardAndFeedbackTest.php` | Verifica métricas, mensagens, moderação e limites de envio. | Validação pelo PHPUnit; `phpunit.xml`. |
| `SecurityAndCompleteFlowTest` | `tests/Feature/SecurityAndCompleteFlowTest.php` | Verifica cabeçalhos, CSRF, cache sensível e os fluxos integrados da loja. | Revisão final; `php artisan test`. |
| `OrdersTest` | `tests/Feature/OrdersTest.php` | Verifica carrinho, checkout, privacidade, estados e concorrência. | Validação pelo PHPUnit; `phpunit.xml`. |
| `AdminUserSeederTest` | `tests/Feature/AdminUserSeederTest.php` | Verifica criação local e preservação do administrador. | Validação pelo PHPUnit; `phpunit.xml`. |
| `ExampleTest` | `tests/Unit/ExampleTest.php` | Executa apenas uma asserção verdadeira de exemplo. | Validação pelo PHPUnit; `phpunit.xml`. |
| Classe anônima de migration (0001_01_01_000000_create_users_table) | `database/migrations/0001_01_01_000000_create_users_table.php` | Cria operadores, tokens de senha e sessões. | Estrutura persistida de autenticação e 2FA. |
| Classe anônima de migration (0001_01_01_000001_create_cache_table) | `database/migrations/0001_01_01_000001_create_cache_table.php` | Cria cache e bloqueios de cache. | Estrutura do cache em banco. |
| Classe anônima de migration (0001_01_01_000002_create_jobs_table) | `database/migrations/0001_01_01_000002_create_jobs_table.php` | Cria jobs, lotes e falhas de fila. | Estrutura das filas em banco. |
| Classe anônima de migration (2026_11_18_000001_create_audit_logs_table) | `database/migrations/2026_11_18_000001_create_audit_logs_table.php` | Cria registros de auditoria e índices. | Persistência de `AuditLog`. |
| Classe anônima de migration (2026_11_18_120000_create_catalog_tables) | `database/migrations/2026_11_18_120000_create_catalog_tables.php` | Cria categorias, produtos e imagens. | Estrutura e vínculos do catálogo. |
| Classe anônima de migration (2026_11_19_000001_create_orders_tables) | `database/migrations/2026_11_19_000001_create_orders_tables.php` | Cria pedidos, itens, chave única e versão. | Estrutura do checkout e atendimento. |
| Classe anônima de migration (2026_11_19_000002_create_messages_and_reviews_tables) | `database/migrations/2026_11_19_000002_create_messages_and_reviews_tables.php` | Cria mensagens e avaliações com versões. | Estrutura de contato e moderação. |

## Padrões de projeto e arquitetura

| Padrão | Onde aparece | Para que serve |
| --- | --- | --- |
| MVC | `app/Http/Controllers/CatalogController.php`, `app/Models/Category.php`, `resources/views/catalog/index.blade.php`; ligação em `routes/web.php`. | Separa entrada HTTP, dados e apresentação. |
| Active Record (Eloquent) | `app/Models/Product.php`; `app/Http/Controllers/Admin/ProductController.php` chama `Product::query()->create()` e `save()`. | O modelo reúne dados, relações, consultas e persistência. |
| Serviços de aplicação | `app/Services/Cart.php`, usado por `app/Http/Controllers/CheckoutController.php`. | Reutiliza validação de itens e cálculo de totais. |
| Injeção de dependências | `app/Http/Controllers/Admin/DashboardController.php` recebe `DashboardMetrics`; `app/Http/Controllers/Auth/TwoFactorSetupController.php` recebe `TwoFactorAuthentication`. | O contêiner fornece colaboradores por tipo. |
| Pipeline de middleware | `app/Http/Middleware/EnsureUserHasRole.php`; aliases em `bootstrap/app.php` e aplicação em `routes/web.php`. | Encadeia verificações e tratamento antes ou depois da ação. |
| Autorização por papéis e policies | `app/Enums/UserRole.php`, `app/Models/User.php`, `app/Policies/OrderPolicy.php`, `app/Http/Requests/Orders/OrderUpdateRequest.php`. | Controla acesso às rotas e ações; pagamento exige administrador. |
| Form Request | `app/Http/Requests/Admin/StoreProductRequest.php`, recebido por `app/Http/Controllers/Admin/ProductController.php`. | Centraliza validação e autorização antes da ação. |
| Regra de validação própria | `app/Rules/ProductImage.php`, usada em `app/Http/Requests/Admin/StoreProductRequest.php`. | Reutiliza inspeção de uploads na validação. |
| Composição por traits | `app/Models/Concerns/HasUniqueSlug.php`, usado em `app/Models/Product.php` e `app/Models/Category.php`. | Compartilha geração de slug sem duplicar código. |
| Herança para reutilizar validação | `app/Http/Requests/Admin/UpdateCategoryRequest.php` estende `StoreCategoryRequest`. | Reaproveita regras e especializa autorização de edição. |
| Factories de dados | `database/factories/ProductFactory.php`, usado por `tests/Feature/CatalogTest.php`. | Cria dados e estados de teste de forma repetível. |
| Herança e composição de templates | `resources/views/catalog/index.blade.php` usa `@extends` e `@include`; layouts em `resources/views/layouts/`. | Reutiliza estrutura de páginas e cartões de produtos. |
| Post/Redirect/Get | `app/Http/Controllers/ContactController.php::store`. | Redireciona após envio e apresenta confirmação na próxima página. |
| Transação com bloqueio pessimista | `app/Http/Controllers/CheckoutController.php::store` e `app/Services/Cart.php::lines`. | Grava pedido e itens juntos e bloqueia produtos e categorias durante a confirmação. |
| Controle de concorrência por versão | `app/Http/Controllers/Admin/OrderController.php::update`; também em mensagens e avaliações. | Rejeita edição desatualizada dentro de transação com bloqueio. |
| Idempotência no checkout | `app/Http/Controllers/CheckoutController.php::store`; chave única em `database/migrations/2026_11_19_000001_create_orders_tables.php`. | Reutiliza pedido já criado quando o mesmo envio se repete. |
| Snapshot dos itens | `app/Http/Controllers/CheckoutController.php::store` e `app/Models/OrderItem.php`. | Preserva nome e preço do produto no momento da compra. |

Repository não foi confirmado: controladores e serviços consultam Eloquent diretamente. Não há camada própria de repositórios. Os serviços existentes não são repositórios.

As transições de atendimento estão em condicionais de `app/Http/Controllers/Admin/OrderController.php`; isso não confirma o padrão State. `Queueable` em `ResetOperatorPassword` não confirma envio assíncrono: a classe não implementa `ShouldQueue`.
