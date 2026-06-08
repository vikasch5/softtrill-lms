<?php

use App\Models\LeadField;
use App\Models\LeadList;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('leads:sample 
    {count=1000000 : Number of leads to generate}
    {--chunk=1000 : Insert chunk size}
    {--user= : User ID to own the generated leads}
    {--list= : Existing lead list ID to use}', function () {
    $count = max((int) $this->argument('count'), 1);
    $chunkSize = max((int) $this->option('chunk'), 100);
    $userId = $this->option('user');
    $listId = $this->option('list');

    $user = $userId
        ? User::find($userId)
        : User::query()->orderBy('id')->first();

    if (!$user) {
        $this->error('No user found. Create at least one user before seeding sample leads.');
        return self::FAILURE;
    }

    $tenantId = $user->tenant_id ?? $user->id;

    $leadList = $listId
        ? LeadList::where('tenant_id', $tenantId)->find($listId)
        : LeadList::where('tenant_id', $tenantId)->orderBy('id')->first();

    if (!$leadList) {
        $leadList = LeadList::create([
            'added_by' => $user->id,
            'tenant_id' => $tenantId,
            'name' => 'Sample Leads',
            'description' => 'Auto generated list for Faker sample leads',
            'is_active' => true,
            'created_by' => $user->id,
        ]);
    }

    $defaultFields = [
        ['name' => 'Source', 'slug' => 'source', 'type' => 'select', 'options' => json_encode(['Facebook', 'Google', 'Instagram', 'Website', 'Referral'])],
        ['name' => 'City', 'slug' => 'city', 'type' => 'text', 'options' => null],
        ['name' => 'Course', 'slug' => 'course', 'type' => 'select', 'options' => json_encode(['Laravel', 'React', 'Digital Marketing', 'Graphic Design', 'Spoken English'])],
        ['name' => 'Budget', 'slug' => 'budget', 'type' => 'number', 'options' => null],
        ['name' => 'Interested Date', 'slug' => 'interested_date', 'type' => 'date', 'options' => null],
    ];

    foreach ($defaultFields as $index => $field) {
        LeadField::firstOrCreate(
            [
                'list_id' => $leadList->id,
                'slug' => $field['slug'],
            ],
            [
                'added_by' => $user->id,
                'tenant_id' => $tenantId,
                'name' => $field['name'],
                'type' => $field['type'],
                'options' => $field['options'],
                'is_required' => false,
                'is_filterable' => true,
                'is_searchable' => true,
                'is_unique' => false,
                'sort_order' => $index + 1,
            ]
        );
    }

    $fields = LeadField::where('list_id', $leadList->id)
        ->orderBy('sort_order')
        ->get(['slug', 'type', 'options']);

    $statusPool = ['new', 'contacted', 'qualified', 'interested', 'followup'];
    $faker = fake();
    $created = 0;

    $this->info("Generating {$count} sample leads in list #{$leadList->id}...");
    $bar = $this->output->createProgressBar($count);
    $bar->start();

    while ($created < $count) {
        $batch = [];
        $limit = min($chunkSize, $count - $created);

        for ($i = 0; $i < $limit; $i++) {
            $name = $faker->name();
            $email = $faker->unique()->safeEmail();
            $phoneNumber = (string) $faker->numerify('##########');
            $status = $faker->randomElement($statusPool);

            $data = [
                'full_name' => $name,
                'name' => $name,
                'email' => $email,
                'phone_number' => $phoneNumber,
            ];

            foreach ($fields as $field) {
                $options = $field->options ? json_decode($field->options, true) : [];

                $data[$field->slug] = match ($field->type) {
                    'email' => $faker->safeEmail(),
                    'phone' => (string) $faker->numerify('##########'),
                    'number' => $faker->numberBetween(1000, 100000),
                    'decimal' => $faker->randomFloat(2, 1000, 100000),
                    'date' => $faker->date(),
                    'datetime' => $faker->dateTimeBetween('-3 months', '+2 months')->format('Y-m-d H:i:s'),
                    'boolean' => $faker->boolean(),
                    'textarea' => $faker->sentence(12),
                    'select', 'radio' => !empty($options) ? $faker->randomElement($options) : $faker->word(),
                    'checkbox' => !empty($options) ? [$faker->randomElement($options)] : [$faker->word()],
                    default => match ($field->slug) {
                        'city' => $faker->city(),
                        'budget' => $faker->numberBetween(15000, 120000),
                        default => $faker->words(2, true),
                    },
                };
            }

            $nextFollowupAt = $faker->optional(0.65)->dateTimeBetween('now', '+30 days');
            $lastFollowupAt = $faker->optional(0.35)->dateTimeBetween('-30 days', 'now');
            $now = now();

            $batch[] = [
                'added_by' => $user->id,
                'tenant_id' => $tenantId,
                'list_id' => $leadList->id,
                'assigned_to' => $user->id,
                'status' => $status,
                'name' => $name,
                'email' => $email,
                'phone_number' => $phoneNumber,
                'email_index' => strtolower($email),
                'phone_index' => $phoneNumber,
                'duplicate_hash' => hash('sha256', $tenantId . '|' . $leadList->id . '|' . $phoneNumber),
                'data' => json_encode($data),
                'last_followup_at' => $lastFollowupAt?->format('Y-m-d H:i:s'),
                'next_followup_at' => $nextFollowupAt?->format('Y-m-d H:i:s'),
                'created_by' => $user->id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('leads')->insert($batch);
        $created += count($batch);
        $bar->advance(count($batch));
    }

    $bar->finish();
    $this->newLine(2);
    $this->info("Created {$created} sample leads successfully.");
    $this->line("User ID: {$user->id}");
    $this->line("Tenant ID: {$tenantId}");
    $this->line("Lead List ID: {$leadList->id}");

    return self::SUCCESS;
})->purpose('Generate a large number of sample leads with Faker');
