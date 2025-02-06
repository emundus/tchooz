---
# https://brenoepics.github.io/vitepress-carbon/guide/home-component.html
layout: home

hero:
  name: "Tchooz"
  text: "by eMundus"
  tagline: Online application management for Joomla 5.x.x
  icon:
    src: ./favicon.ico
    alt: Tchooz Icon
  image:
    src: ./bg.png
    alt: Banner
  actions:
    - theme: brand
      text: Get Started
      link: /docs/getting-started

features:
  - title: Booking
    details: This module enables managers to define events and booking slots for their applicants. It also allows applicants to book slots for those events.
    icon: 📅
    link: /docs/backend/features/booking
  - title: Workflow Builder
    details: This module allows managers to create and manage workflows for their applicants. A workflow is a series of steps that an applicant must complete in order to apply for a position. Managers can define the steps, assign them to different users, and track the progress of each applicant through the workflow.
    icon: 🔀
    link: /docs/backend/features/workflow-builder
  - title: Messenger
    details: This module allows to send messages between an applicant and managers. It is used to communicate with the applicant about the candidate's application.
    icon: 📧
    link: /docs/backend/features/messenger
---

