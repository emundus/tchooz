---
outline: deep
---

# Messenger

[[toc]]

## Overview
This module allows to send messages between an applicant and managers. It is used to communicate with the applicant about the candidate's application.

## Features
- Applicants can send messages to managers related to their application.
- Managers can send messages to applicants related to their application.
- Notifications are sent to the applicant and managers when a new message is received.
- Some settings can be defined by the administrator in the add-ons section of the platform settings
  - Anonymise managers' names when sending messages
  - Send a summary e-mail of unanswered/treated messages
    - Frequency of sending the summary e-mail
      - Daily (default)
      - Weekly
      - Custom
    - List of recipients of the summary e-mail
      - Users associated with the application file
      - All users of a specific group/profile
      - Custom list of users
      - Setup by status
        - Profile(s) to be notified / Status(es)

## Data Model
The data model for the messenger module is as follows:
<!--@include: ./databases/messenger.md-->

## Vue Components
The Vue components for the messenger module are as follows:
<!--@include: ./vue-components/messenger.md-->