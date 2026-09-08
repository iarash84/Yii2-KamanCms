<?php

namespace frontend\models;

use Yii;
use yii\db\Query;

class VisitorReport
{
    public static function dashboard($days = 30)
    {
        $days = max(7, min(90, (int) $days));
        $today = gmdate('Y-m-d');
        $start = gmdate('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
        $previousStart = gmdate('Y-m-d', strtotime('-' . (($days * 2) - 1) . ' days'));
        $previousEnd = gmdate('Y-m-d', strtotime('-' . $days . ' days'));

        $totals = self::totals($start, $today);
        $previous = self::totals($previousStart, $previousEnd);
        $totals['inquiries'] = self::inquiryCount($start, $today);
        $previous['inquiries'] = self::inquiryCount($previousStart, $previousEnd);
        $totals['inquiry_rate'] = $totals['visitors'] > 0
            ? round(($totals['inquiries'] / $totals['visitors']) * 100, 1)
            : 0.0;
        $daily = (new Query())->from('{{%visitor_daily}}')->where(['between', 'visit_date', $start, $today])
            ->orderBy(['visit_date' => SORT_ASC])->all();
        $countries = (new Query())->select(['country_code', 'page_views' => 'SUM([[page_views]])', 'visitors' => 'SUM([[visitors]])'])
            ->from('{{%visitor_country_daily}}')->where(['between', 'visit_date', $start, $today])
            ->groupBy('country_code')->orderBy(['page_views' => SORT_DESC])->limit(8)->all();
        $pages = (new Query())->select(['path', 'page_views' => 'SUM([[page_views]])', 'visitors' => 'SUM([[visitors]])'])
            ->from('{{%visitor_page_daily}}')->where(['between', 'visit_date', $start, $today])
            ->groupBy('path')->orderBy(['page_views' => SORT_DESC])->limit(8)->all();

        return [
            'days' => $days,
            'totals' => $totals,
            'trend' => [
                'page_views' => self::trend($totals['page_views'], $previous['page_views']),
                'visitors' => self::trend($totals['visitors'], $previous['visitors']),
                'inquiries' => self::trend($totals['inquiries'], $previous['inquiries']),
            ],
            'daily' => $daily,
            'countries' => $countries,
            'pages' => $pages,
        ];
    }

    private static function totals($start, $end)
    {
        $row = (new Query())->select(['page_views' => 'COALESCE(SUM([[page_views]]), 0)', 'visitors' => 'COALESCE(SUM([[visitors]]), 0)'])
            ->from('{{%visitor_daily}}')->where(['between', 'visit_date', $start, $end])->one();
        return ['page_views' => (int) $row['page_views'], 'visitors' => (int) $row['visitors']];
    }

    private static function inquiryCount($start, $end): int
    {
        $range = ['between', 'created_at', $start . ' 00:00:00', $end . ' 23:59:59'];

        return (int) Contact::find()->where($range)->count()
            + (int) Order::find()->where($range)->count();
    }

    private static function trend($current, $previous)
    {
        if ((int) $previous === 0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }
}
