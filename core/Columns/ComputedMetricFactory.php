<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Columns;

use Piwik\Piwik;
use Piwik\Plugin\ArchivedMetric;
use Piwik\Plugin\ComputedMetric;
use Piwik\Plugin\Report;

class ComputedMetricFactory
{
    /**
     * @var MetricsList
     */
    private $metricsList = null;

    /**
     * Generates a new report widget factory.
     * @param Report $report  A report instance, widgets will be created based on the data provided by this report.
     */
    public function __construct(MetricsList $list)
    {
        $this->metricsList = $list;
    }

    /**
     * @return \Piwik\Plugin\ComputedMetric
     */
    public function createComputedMetric($metricName1, $metricName2, $aggregation)
    {
        $metric1 = $this->metricsList->getMetric($metricName1);
        $metric2 = $this->metricsList->getMetric($metricName2);

        if (!$metric1 instanceof ArchivedMetric || !$metric1->getDimension()) {
            throw new \Exception('Only possible to create computed metric for an archived metric with a dimension');
        }

        $dimension1 = $metric1->getDimension();

        $dimension2 = null;
        if ($metric2 instanceof ArchivedMetric) {
            $dimension2 = $metric2->getDimension();
        }

        if ($aggregation === ComputedMetric::AGGREGATION_AVG) {
            $name = 'avg_' . $metricName1 . '_per_' . $metricName2;

            if ($dimension1 && $dimension2) {
                $translatedName = Piwik::translate('General_ComputedMetricAverage', array($dimension1->getName(), $dimension2->getName()));
            } else {
                $translatedName = Piwik::translate('General_AverageX', array($dimension1->getName()));
            }

            if (empty($metric2)) {
                $x = 1;
            }
            $documentation = Piwik::translate('General_ComputedMetricAverageDocumentation', array($metric1->getTranslatedName(), $metric2->getTranslatedName()));

        } elseif ($aggregation === ComputedMetric::AGGREGATION_RATE) {
            $name = $metricName1 . '_rate';
            $translatedName = null;
            $documentation = Piwik::translate('General_ComputedMetricRateDocumentation', array($dimension1->getNamePlural(), $dimension2->getNamePlural()));
        } else {
            throw new \Exception('Not supported aggregation type');
        }

        $name = str_replace(array('nb_uniq_', 'nb_with_', 'uniq_', 'nb_', 'sum_', 'max_', 'min_', '_count'), '', $name);

        $metric = new ComputedMetric($metricName1, $metricName2, $aggregation);
        if ($aggregation === ComputedMetric::AGGREGATION_RATE) {
            $metric->setType(Dimension::TYPE_PERCENT);
        } else {
            $metric->setType($dimension1->getType());
        }
        $metric->setName($name);
        $metric->setTranslatedName($translatedName);
        $metric->setDocumentation($documentation);
        $metric->setCategory($dimension1->getCategoryId());
        return $metric;
    }

}