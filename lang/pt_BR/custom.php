<?php

return [

    'title' => [
        'category'     => 'Categoria',
        'categories'   => 'Categorias',
        'family'       => 'Família',
        'families'     => 'Famílias',
        'account'      => 'Conta',
        'accounts'     => 'Contas',
        'transaction'  => 'Transação',
        'transactions' => 'Transações',
        'launch'       => 'Lançamento',
        'releases'     => 'Lançamentos',
        'dashboard'    => 'Painel Principal',
        'user'         => 'Usuário',
        'users'        => 'Usuários',
        'setting'      => 'Configuração',
        'settings'     => 'Configurações',
        'security'     => 'Segurança',
        'report'       => 'Relatório',
        'reports'      => 'Relatórios',
        'credit_card'  => 'Cartão de crédito',
        'credit_cards' => 'Cartões de crédito',
        'admin'        => 'Administração',
        'brand'        => 'Logos',
        'invoices'     => 'Faturas',
    ],

    'notification' => [
        'family_not_found' => 'Nenhuma família ativa encontrada para associar o usuário.',
        'email_duplicate'  => 'E-mail já existente.'
    ],

    'navigation-groups' => [
        'settings' => 'Configurações',
        'admin'    => 'Administração',
    ],

    'type' => [
        // Tipos de Conta e Origem
        'current_account' => 'Conta Corrente',
        'savings_account' => 'Conta Poupança',
        'account'         => 'Conta',
        'credit_card'     => 'Cartão de Crédito',
        // Tipos de Movimentação
        'expense'         => 'Despesa',
        'income'          => 'Receita',
        'transfer'        => 'Transferência',
    ],
    'status' => [

        // Status de Transações e Parcelas
        'pending'         => 'Pendente',
        'posted'          => 'Lançado',
        'scheduled'       => 'Agendado',
        'overdue'         => 'Vencido',
        'canceled'        => 'Cancelado',
        'paid'            => 'Pago',
        'cleared'         => 'Compensado',
        'partial'         => 'Parcial',
        'refunded'        => 'Reembolso',
        'cancelled' => 'Cancelado',

        // Status de Entidade e Faturas (Novos)
        'open'            => 'Aberto',
        'closed'          => 'Fechado',
        'active'          => 'Ativo',
        'inactive'        => 'Inativo',

        // Status de Assinatura e SaaS (Novos)
        'trial'           => 'Período de Teste',
        'late_payment'    => 'Pagamento em Atraso',
    ]

];
