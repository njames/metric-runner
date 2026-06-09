<?php

namespace YourOrg\MetricRunner\Exceptions;

class MetricNotFoundException extends \RuntimeException {}
class MetricNotApprovedException extends \RuntimeException {}
class MetricAccessDeniedException extends \RuntimeException {}
class InvalidMetricException extends \RuntimeException {}
class ClickHouseQueryException extends \RuntimeException {}
