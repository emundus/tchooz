#!/bin/bash

RED='\033[0;31m'
INFO='\033[0;36m'
SUCCESS='\033[0;32m'
NC='\033[0m'

printf "${RED}Danger !\n${NC}This script will clean the installation folder, please stop your containers before running it.\n${RED}Are you sure ? (y/n)${NC}\n"
read confirm

if [ "$confirm" != "y" ]; then
    exit 0
fi

printf "${INFO}Running clean...\n"
printf "${NC}Removing g5_helium files...\n"
rm -rf ../../templates/g5_helium/custom/config/*
rm -rf ../../templates/g5_helium/templateDetails.xml
printf "${SUCCESS}g5_helium files removed\n"

printf "${NC}Clear namespace cache\n"
rm -rf ../../administrator/cache/*
printf "${SUCCESS}Cache cleared\n"

printf "${NC}Remove configuration files\n"
rm -rf ../../.htaccess
rm -rf ../../configuration.php
printf "${SUCCESS}Configuration cleared\n"

printf "${NC}Do you want to clear your database too ? (y/n)${NC}\n"
read clear_database

if [ "$clear_database" != "y" ]; then
    exit 0
fi

printf "${NC}Clear database\n"
rm -rf ../data/mysql
printf "${SUCCESS}All is cleared you can re-deploy your project\n"