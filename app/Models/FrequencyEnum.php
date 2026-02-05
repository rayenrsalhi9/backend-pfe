<?php

namespace App\Models;

enum FrequencyEnum: int
{
    case Daily = 0;
    case Weekly = 1;
    case Monthly = 2;
    case Quarterly = 3;
    case HalfYearly = 4;
    case Yearly = 5;
    case OneTime = 6;
}
