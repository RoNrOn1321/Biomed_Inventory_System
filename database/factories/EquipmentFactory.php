<?php

namespace Database\Factories;

use App\Models\Equipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Equipment>
 */
class EquipmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location'      => $this->faker->randomElement(['ICU', 'Operating Room', 'Emergency Room', 'Radiology Department', 'Laboratory', 'General Ward', 'Pharmacy']),
            'description'   => $this->faker->randomElement(['Patient Monitor', 'Anesthesia Machine', 'Electrosurgical Unit', 'Defibrillator', 'Infusion Pump', 'Ultrasound Machine', 'X-Ray Machine', 'Ventilator', 'ECG Machine', 'Dialysis Machine']),
            'brand'         => $this->faker->company(),
            'model'         => $this->faker->bothify('Model-####??'),
            'serial_number' => $this->faker->unique()->bothify('SN-########'),
            'tag_number'    => $this->faker->unique()->bothify('TAG-####'),
            'pm_date_done'  => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'calibration'   => $this->faker->randomElement(['Uncalibrated', 'Due for Calibration']),
            'status'        => $this->faker->randomElement(['Functional', 'Defective', 'Unserviceable']),
        ];
    }
}
