<?php

namespace Database\Seeders;

use App\Collections\BlogCollection;
use App\Collections\UserCollection;
use App\Models\Blog;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class BlogsSeeder extends BaseSeeder
{
    public function seed(): void
    {
        $this->seedFastest();
    }

    protected function seedFastest(): void
    {
        $this->callOnce(UsersSeeder::class);
        $this->benchmarkAttempt3();
    }

    protected function benchmark(): void
    {
        Config::set('laravel-kata.dummy-data.max-users', 10);
        Config::set('laravel-kata.dummy-data.max-user-blogs', 10);

        DB::table('users')->truncate();
        $this->call(UsersSeeder::class);

        $debug = function (callable $callable) {
            $start = microtime(true);
            $callable();
            $end = microtime(true);

            return [
                'seconds' => ($end - $start),
                'checksum' => $this->benchmarkChecksum(),
            ];
        };

        $rows = [];
        $attempts = [
            1,
            2,
            3,
            4,
        ];
        foreach ($attempts as $i) {
            DB::table('blogs')->truncate();

            $function = sprintf('benchmarkAttempt%d', $i);
            $result = $debug(fn () => $this->{$function}());

            $rows[] = [
                $function,
                $result['seconds'],
                $result['checksum'],
            ];
        }

        $this->command->info('# Report');
        $this->command->info(sprintf(
            "Max users: %d\nMax blogs per user: %d",
            Config::get('laravel-kata.dummy-data.max-users'),
            Config::get('laravel-kata.dummy-data.max-user-blogs')
        ));
        $this->command->table([
            'Function',
            'Execution time (s)',
            'Checksum',
        ], $rows);
    }

    protected function benchmarkChecksum(): string
    {
        // $sql = "CHECKSUM TABLE users, blogs;"; // Would be perfect, but randomised & timestamps
        $sqls = [
            "SELECT 'laravel.users' AS `Table`, COUNT(id) AS `Checksum` FROM users",
            "SELECT 'laravel.blogs' AS `Table`, COUNT(id) AS `Checksum` FROM blogs",
        ];

        $sql = implode("\nUNION\n", $sqls);
        $rows = DB::select(DB::raw($sql));

        if (empty($rows)) {
            throw new Exception(sprintf('Unexpected empty checksums'));
        }

        $checksums = collect();
        foreach ($rows as $row) {
            $checksums->push($row?->Checksum ?? 0);
        }

        return md5($checksums->sum());
    }

    protected function benchmarkAttempt1(): void
    {
        $maxUserBlogs = config('laravel-kata.dummy-data.max-user-blogs');
        $this->command->withProgressBar(User::all(), function (User $user) use ($maxUserBlogs) {
            if ($user->blogs()->count() >= $maxUserBlogs) {
                return;
            }

            Blog::factory($maxUserBlogs, [
                'user_id' => $user->id,
            ])->create();
        });
        $this->command->newLine();
    }

    protected function benchmarkAttempt2(): void
    {
        $maxUserBlogs = config('laravel-kata.dummy-data.max-user-blogs');
        $blogs = BlogCollection::make();
        $this->command->withProgressBar(User::all(), function (User $user) use ($maxUserBlogs, &$blogs) {
            if ($user->blogs()->count() >= $maxUserBlogs) {
                return;
            }

            $fakeBlogs = Blog::factory($maxUserBlogs, [
                'user_id' => $user->id,
            ])->make();

            $blogs->push(...$fakeBlogs);
        });
        $blogs->upsert();
        $this->command->newLine();
    }

    protected function benchmarkAttempt3(): void
    {
        $maxUserBlogs = config('laravel-kata.dummy-data.max-user-blogs');
        $this->command->withProgressBar(User::all()->chunk(100), function (UserCollection $users) use ($maxUserBlogs) {
            $blogs = BlogCollection::make();

            foreach ($users as $user) {
                if ($user->blogs()->count() >= $maxUserBlogs) {
                    return;
                }

                $fakeBlogs = Blog::factory($maxUserBlogs, [
                    'user_id' => $user->id,
                ])->make();

                $blogs->push(...$fakeBlogs);
            }

            $blogs->upsert();
        });
        $this->command->newLine();
    }

    protected function benchmarkAttempt4(): void
    {
        $maxUserBlogs = config('laravel-kata.dummy-data.max-user-blogs');
        $this->command->withProgressBar(User::all()->chunk(100), function (UserCollection $users) use ($maxUserBlogs) {
            $blogs = BlogCollection::make();

            foreach ($users as $user) {
                if ($user->blogs()->count() >= $maxUserBlogs) {
                    return;
                }

                $fakeBlogs = Blog::factory($maxUserBlogs, [
                    'user_id' => $user->id,
                ])->make();

                $blogs->push(...$fakeBlogs);
            }

            $blogs->chunk(10)->each(fn (BlogCollection $chunkedBlogs) => $chunkedBlogs->upsert());
        });

        $this->command->newLine();
    }
}
