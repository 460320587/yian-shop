<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Content\Models\Article;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $articles = [
            [
                'title' => '怡安印刷商城正式上线',
                'slug' => 'company-intro',
                'type' => 1,
                'content' => '怡安印刷商城是一家专注于印刷品定制的一站式服务平台，提供名片、画册、包装盒、标签、广告物料等多种印刷品定制服务。',
                'summary' => '怡安印刷商城正式上线运营',
                'author' => '编辑部',
                'sort' => 1,
                'status' => 1,
            ],
            [
                'title' => '2026年春节放假通知',
                'slug' => 'spring-holiday',
                'type' => 2,
                'content' => '尊敬的客户：2026年春节放假时间为1月25日至2月2日，2月3日正式上班。放假期间可正常下单，节后统一安排生产。',
                'summary' => '2026年春节放假安排',
                'author' => '运营部',
                'sort' => 2,
                'status' => 1,
            ],
            [
                'title' => '如何下单',
                'slug' => 'how-to-order',
                'type' => 3,
                'content' => '1. 选择商品 -> 2. 填写参数 -> 3. 上传文件 -> 4. 确认订单 -> 5. 支付 -> 6. 等待生产发货',
                'summary' => '详细的下单流程说明',
                'author' => '客服部',
                'sort' => 1,
                'status' => 1,
            ],
            [
                'title' => '印刷文件规范',
                'slug' => 'file-spec',
                'type' => 3,
                'content' => '请上传PDF格式文件，分辨率不低于300dpi，颜色模式为CMYK，出血位3mm。',
                'summary' => '印刷文件制作规范',
                'author' => '技术部',
                'sort' => 2,
                'status' => 1,
            ],
            [
                'title' => '会员权益说明',
                'slug' => 'vip-benefits',
                'type' => 3,
                'content' => '注册即可成为普通会员，累计消费可升级会员等级，享受更多折扣和特权。',
                'summary' => '会员等级及权益说明',
                'author' => '运营部',
                'sort' => 3,
                'status' => 1,
            ],
            [
                'title' => '关于我们',
                'slug' => 'about-us',
                'type' => 4,
                'content' => '怡安印刷拥有20年印刷行业经验，服务超过10万家企业客户，致力于为客户提供高品质、高效率的印刷解决方案。',
                'summary' => '怡安印刷公司简介',
                'author' => '编辑部',
                'sort' => 1,
                'status' => 1,
            ],
        ];

        foreach ($articles as $data) {
            Article::updateOrCreate(['slug' => $data['slug']], $data + ['view_count' => 0]);
        }
    }
}
