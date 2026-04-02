<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('source_type', ['local', 'git']);
            $table->string('local_path')->nullable();
            $table->string('git_url')->nullable();
            $table->string('branch')->default('main');
            $table->string('ip_address')->default('0.0.0.0');
            $table->unsignedSmallInteger('port');
            $table->enum('status', ['stopped', 'running', 'deploying', 'error'])->default('stopped');
            $table->string('container_id')->nullable();
            $table->boolean('auto_deploy')->default(false);
            $table->unsignedInteger('auto_deploy_interval')->default(5)->comment('Polling interval in minutes');
            $table->string('last_commit_hash')->nullable();
            $table->timestamp('last_deployed_at')->nullable();
            $table->foreignId('git_credential_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique('port');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
