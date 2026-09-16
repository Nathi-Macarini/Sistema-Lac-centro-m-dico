<div align="center">

<img src="logo_lac.png" alt="LAC Centro Médico" width="180"/>

# LAC Centro Médico

### Sistema de Gestão Clínica e Portal do Paciente

Plataforma web para gestão de atendimentos médicos, teleconsultas e cadastro de pacientes — com painel administrativo completo, login flexível por e-mail ou CPF e recuperação de senha.

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/Tailwind-CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)
![License](https://img.shields.io/badge/Uso-Acadêmico-C5A059?style=for-the-badge)

### ✨ Contribuidores

<table>
  <tr>
    <td align="center">
      <a href="https://github.com/GreenStar15">
        <img src="https://avatars.githubusercontent.com/GreenStar15" width="100px;" alt=""/>
        <br />
        <sub><b>GreenStar15</b></sub>
      </a>
    </td>
    <td align="center">
      <a href="https://github.com/Henrique331">
        <img src="https://avatars.githubusercontent.com/Henrique331" width="100px;" alt=""/>
        <br />
        <sub><b>Henrique331</b></sub>
      </a>
    </td>
    <td align="center">
      <a href="https://github.com/joao232005">
        <img src="https://avatars.githubusercontent.com/joao232005" width="100px;" alt=""/>
        <br />
        <sub><b>joao232005</b></sub>
      </a>
    </td>
    <td align="center">
      <a href="https://github.com/Nathi-Macarini">
        <img src="https://avatars.githubusercontent.com/Nathi-Macarini" width="100px;" alt=""/>
        <br />
        <sub><b>Nathi-Macarini</b></sub>
      </a>
    </td>
    <td align="center">
      <a href="https://github.com/Danilo-bit311">
        <img src="https://avatars.githubusercontent.com/Danilo-bit311" width="100px;" alt=""/>
        <br />
        <sub><b>Danilo-bit311</b></sub>
      </a>
    </td>
  </tr>
</table>

</div>

## 💡 Sobre o Projeto

O **LAC Centro Médico** é um sistema web desenvolvido para modernizar o atendimento de clínicas médicas e estéticas. Ele integra, em um único ambiente:

- **Portal do Paciente** — agendamento de consultas, teleconsulta e histórico.
- **Painel Administrativo** — gestão de usuários, médicos e cadastros.
- **Autenticação inteligente** — login por e-mail **ou** CPF.
- **Recuperação de senha** — fluxo de redefinição seguro via token.

O projeto foi construído priorizando **experiência do usuário**, **identidade visual sofisticada** (tons de dourado, marrom e bege) e **código limpo e organizado**, seguindo boas práticas de PHP e MySQL.

## ✨ Funcionalidades

### 👤 Portal do Paciente
- Login com **e-mail ou CPF**
- Saudação personalizada no painel
- Agendamento de consultas (presencial ou por vídeo)
- Pronto atendimento via teleconsulta (24h/7d)
- Verificação de câmera e microfone antes da consulta
- Histórico de consultas agendadas
- Suporte via WhatsApp integrado
- Página de **perfil** com dados pessoais
- **Troca de senha** com medidor de força animado

### 🛡️ Painel Administrativo
- Acesso exclusivo para usuários do tipo `admin`
- Botão dedicado no cabeçalho do site (só aparece para admin)
- **Cadastro completo de usuários**:
  - Nome, e-mail, CPF, telefone, data de nascimento e endereço
  - Validação: precisa ter **e-mail OU CPF**
  - **Senha inicial = CPF** (sem pontuação)
- **Cadastro de médicos**:
  - Nome, CRM, especialidade, e-mail e telefone
  - Validação de CRM único
- Listagem de usuários e médicos em tabelas organizadas

### 🔐 Autenticação e Segurança
- Login por e-mail ou CPF
- Recuperação de senha via **token com expiração de 30 minutos**
- Token de uso único (não pode ser reutilizado)
- Proteção de páginas internas via `session_start()`
- Redirecionamento automático de usuários não autorizados
- **Medidor de força de senha** (5 níveis: muito fraca → forte)
- Checklist dinâmico de regras de senha
- Confirmação de senha em tempo real

## 🛠️ Tecnologias Utilizadas

| Camada | Tecnologia |
|---|---|
| **Backend** | PHP 8.2 |
| **Banco de Dados** | MySQL 8.0 |
| **Frontend** | HTML5, Tailwind CSS 3 (via CDN) |
| **Ícones** | Font Awesome 6.4 |
| **Fontes** | Google Fonts (serif + sans) |
| **API do Navegador** | MediaDevices (câmera/microfone) |

## 🚀 Instalação

### Pré-requisitos
- PHP **8.0+**
- MySQL **5.7+** ou MariaDB **10.4+**
- Servidor web local (XAMPP, WAMP, Laragon ou similar)

### Passo a passo

**1. Clone o repositório**

git clone https://github.com/seu-usuario/Sistema-Lac-centro-m-dico.git

**2. Mova a pasta para o diretório do servidor**

XAMPP: C:\xampp\htdocs\LAC_Centro_Medico\

WAMP: C:\wamp64\www\LAC_Centro_Medico\

Laragon: C:\laragon\www\LAC_Centro_Medico\

**3. Importe o banco de dados**

Abra o phpMyAdmin: http://localhost/phpmyadmin

Clique em Importar

Selecione o arquivo banco.sql

Clique em Executar

O banco lac_centro_medico e todas as tabelas serão criados automaticamente, incluindo usuários de teste.

**4. Configure as credenciais do banco (se necessário)**

Os arquivos PHP usam a configuração padrão:

php
new mysqli('localhost', 'root', '', 'lac_centro_medico');
Se sua instalação tiver senha no MySQL, ajuste em todos os arquivos .php.

**5. Acesse o sistema**

http://localhost/LAC_Centro_Medico/Sistema-Lac-centro-m-dico/login.php
