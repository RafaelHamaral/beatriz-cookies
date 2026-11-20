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

A vitrine fica em `http://127.0.0.1:8000` e o catálogo em `http://127.0.0.1:8000/catalogo`. O login fica em `http://127.0.0.1:8000/login`.

O seeder só roda com `APP_ENV=local`, não faz parte do `DatabaseSeeder` e não troca a senha nem o perfil se a conta já existir. A senha é gravada com `Hash::make()`.

No ambiente local o e-mail de redefinição de senha vai para `storage/logs/laravel.log`.

## Testes

```bash
php artisan test
```

Os testes usam SQLite em memória e senhas geradas pela factory. Eles não usam a senha do `.env`.

## Próxima etapa

Revisão final de segurança e validação dos fluxos completos. A configuração de produção está em `docs/producao.md`.

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

`php artisan test` verifica cálculos, preços alterados, disponibilidade, checkout duplicado, permissões, filtros, rollback, pagamento duplicado e versões desatualizadas. Os testes usam SQLite em memória; não simulam dois processos concorrentes no MySQL.

## Etapa 4 — dashboard, mensagens e avaliações

- `/area-restrita` mostra faturamento, unidades, pedidos pendentes, movimento diário e ranking de produtos com um único filtro de período.
- Faturamento e unidades consideram apenas pedidos pagos pela data da confirmação. Pedidos cancelados ou estornados ficam fora dos números.
- `/contato` recebe mensagens públicas. Admin e editor acompanham leitura e atendimento em `/area-restrita/mensagens`.
- `/avaliar` recebe notas de 1 a 5. Avaliações começam pendentes e só aparecem na página inicial depois da aprovação em `/area-restrita/avaliacoes`.
- Badges na navegação informam pedidos aguardando confirmação, mensagens não lidas e avaliações pendentes.
- Viewer consulta somente o dashboard. Admin e editor operam mensagens e avaliações.
