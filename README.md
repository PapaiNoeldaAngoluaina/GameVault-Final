# GameVault

GameVault é uma loja fictícia de jogos digitais que comercializa chaves de ativação da Steam. Projeto acadêmico: não há integração com a Steam, pagamentos reais nem chaves legítimas. As chaves geradas na compra são fictícias.

## Tecnologias

- PHP 8.x (PDO)
- MySQL/MariaDB
- HTML5, CSS3, Bootstrap, JavaScript

## Funcionalidades

- Catálogo com 20 jogos carregados do banco de dados
- Pesquisa por nome e filtro por categoria
- Página de detalhes do jogo
- Carrinho de compras com sessões PHP
- Checkout simulado com registro do pedido, itens e uma chave fictícia por unidade comprada
- Histórico de compras da sessão atual

## Configuração (XAMPP)

1. Copie a pasta `GameVault` para `C:\xampp\htdocs\`.
2. Abra o painel do XAMPP e inicie o **Apache** e o **MySQL**.

## Banco de dados

1. Acesse `http://localhost/phpmyadmin`.
2. Clique em **Importar**, escolha o arquivo `gamevault.sql` e execute.
3. O script cria o banco `gamevault`, as tabelas e os jogos. Pode ser importado novamente para atualizar o catálogo.
4. Se o MySQL usar outro usuário ou senha, ajuste `DB_USER` e `DB_PASS` em `src/config.php`.

## Executando o projeto

Acesse `http://localhost/GameVault/public/`.
