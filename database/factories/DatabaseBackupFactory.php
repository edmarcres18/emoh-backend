<?php

namespace Database\Factories;

use App\Models\DatabaseBackup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DatabaseBackup>
 */
class DatabaseBackupFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DatabaseBackup::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(['completed', 'failed', 'in_progress', 'pending']);
        $createdAt = $this->faker->dateTimeBetween('-30 days', 'now');
        $completedAt = $status === 'completed' ? $this->faker->dateTimeBetween($createdAt, 'now') : null;
        $filename = 'backup_' . $createdAt->format('Y_m_d_His') . '_' . $this->faker->randomAscii() . '.sql';

        return [
            'filename' => $filename,
            'file_path' => storage_path('app/backups/' . $filename),
            'file_size' => $status === 'completed' ? $this->faker->numberBetween(1000000, 100000000) : 0,
            'status' => $status,
            'error_message' => $status === 'failed' ? $this->faker->sentence() : null,
            'type' => $this->faker->randomElement(['manual', 'scheduled']),
            'scheduled_at' => $this->faker->optional(0.3)->dateTimeBetween('now', '+30 days'),
            'completed_at' => $completedAt,
        ];
    }

    /**
     * Indicate that the backup is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_at' => $this->faker->dateTimeBetween($attributes['created_at'] ?? '-1 hour', 'now'),
            'file_size' => $this->faker->numberBetween(1000000, 100000000),
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the backup failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'completed_at' => $this->faker->dateTimeBetween($attributes['created_at'] ?? '-1 hour', 'now'),
            'file_size' => 0,
            'error_message' => $this->faker->randomElement([
                'Database connection failed',
                'Insufficient disk space',
                'Permission denied',
                'Timeout occurred during backup',
                'Invalid database credentials',
            ]),
        ]);
    }

    /**
     * Indicate that the backup is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'completed_at' => null,
            'file_size' => 0,
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the backup is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'completed_at' => null,
            'file_size' => 0,
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the backup is manual.
     */
    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'manual',
        ]);
    }

    /**
     * Indicate that the backup is scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'scheduled',
        ]);
    }
}
