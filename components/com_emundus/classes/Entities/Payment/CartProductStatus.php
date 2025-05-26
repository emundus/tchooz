<?php

namespace Tchooz\Entities\Payment;

enum CartProductStatus: string {
	case PENDING = 'pending';
	case ADVANCE = 'advance';
	case PAID    = 'paid';
}
