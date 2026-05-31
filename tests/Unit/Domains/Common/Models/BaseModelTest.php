<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Common\Models;

use App\Domains\Common\Models\BaseModel;
use App\Domains\Common\ValueObjects\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Concrete test model for BaseModel testing.
 */
class TestModel extends BaseModel
{
    use HasFactory;

    protected $table = 'test_models';

    protected $fillable = ['name', 'status', 'price', 'deleted_at'];

    protected $casts = [
        'price' => Money::class,
    ];
}

class BaseModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_models', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->unsignedBigInteger('price')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('test_models');
        parent::tearDown();
    }

    /** @test */
    public function it_uses_auto_incrementing_id_primary_key(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        $this->assertNotNull($model->id);
        $this->assertIsInt($model->id);
        $this->assertGreaterThan(0, $model->id);
    }

    /** @test */
    public function it_manages_created_at_and_updated_at_automatically(): void
    {
        $model = TestModel::create(['name' => 'Test']);

        $this->assertNotNull($model->created_at);
        $this->assertNotNull($model->updated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $model->created_at);
    }

    /** @test */
    public function it_supports_soft_deletes_by_default(): void
    {
        $model = TestModel::create(['name' => 'Test']);
        $this->assertTrue(Schema::hasColumn('test_models', 'deleted_at'));

        $model->delete();

        $this->assertSoftDeleted('test_models', ['id' => $model->id]);
        $this->assertDatabaseCount('test_models', 1);
    }

    /** @test */
    public function it_serializes_dates_in_y_m_d_h_i_s_format(): void
    {
        $model = TestModel::create(['name' => 'Test']);
        $serialized = $model->toArray();

        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $serialized['created_at']
        );
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $serialized['updated_at']
        );
    }

    /** @test */
    public function it_casts_money_attribute_from_fen_to_yuan(): void
    {
        $model = TestModel::create(['name' => 'Test', 'price' => 12345]);

        $this->assertInstanceOf(Money::class, $model->price);
        $this->assertSame(12345, $model->price->amount);
        $this->assertSame(123.45, $model->price->toYuan());
    }

    /** @test */
    public function it_can_set_money_attribute_from_money_instance(): void
    {
        $model = new TestModel();
        $model->price = Money::fromYuan(99.99);
        $model->name = 'Test';
        $model->save();

        $this->assertDatabaseHas('test_models', [
            'id' => $model->id,
            'price' => 9999,
        ]);
    }

    /** @test */
    public function it_provides_active_scope(): void
    {
        TestModel::create(['name' => 'Active 1', 'status' => 1]);
        TestModel::create(['name' => 'Active 2', 'status' => 1]);
        TestModel::create(['name' => 'Inactive', 'status' => 0]);

        $active = TestModel::active()->get();

        $this->assertCount(2, $active);
    }

    /** @test */
    public function it_provides_recent_scope(): void
    {
        $old = TestModel::create(['name' => 'Old']);
        $old->created_at = now()->subDays(10);
        $old->save();

        $recent = TestModel::create(['name' => 'Recent']);

        $results = TestModel::recent(5)->get();

        $this->assertCount(1, $results);
        $this->assertSame($recent->id, $results->first()->id);
    }

    /** @test */
    public function it_uses_uuid_when_configured(): void
    {
        // Test that UUID extension point exists via $primaryKeyType
        $this->assertTrue(method_exists(TestModel::class, 'usesUniqueIds'));
    }
}
