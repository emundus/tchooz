<?php

enum EmundusColumnTypeEnum: string
{
	case TEXT = 'TEXT';
	case VARCHAR = 'VARCHAR';
	case INT = 'INT';
	case FLOAT = 'FLOAT';
	case DATE = 'DATE';
	case DATETIME = 'DATETIME';
	case BOOLEAN = 'BOOLEAN';
	case JSON = 'JSON';
	case TINYINT = 'TINYINT';
	case BLOB = 'BLOB';
}
