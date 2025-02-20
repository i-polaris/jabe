<?php

namespace Jabe\Engine\Management;

interface MetricIntervalValueInterface
{
    /**
     * Returns the name of the metric.
     *
     * @see constants in {@link Metrics} for a list of names which can be returned here
     *
     * @return the name of the metric
     */
    public function getName(): string;

    /**
     * Returns
     *        the reporter name of the metric, identifies the node which generates this metric.
     *        'null' when the metrics are aggregated by reporter.
     *
     * @return the reporter name
     */
    public function getReporter(): string;

    /**
     * Returns the timestamp as date object, on which the metric was created.
     *
     * @return the timestamp
     */
    public function getTimestamp(): string;

    /**
     * Returns the value of the metric.
     *
     * @return the value
     */
    public function getValue(): int;
}
