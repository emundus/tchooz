<?php

enum EmundusTableForeignKeyOnEnum: string
{
	case NO_ACTION = 'NO ACTION';
	case CASCADE = 'CASCADE';
	case SET_NULL = 'SET NULL';
	case SET_DEFAULT = 'SET DEFAULT';
	case RESTRICT = 'RESTRICT';
}
