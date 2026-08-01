<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invitation_designs')) {
            Schema::create('invitation_designs', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120);
                $table->string('slug', 140)->unique();
                $table->string('description', 500)->nullable();
                $table->string('tier', 20)->default('standard'); // standard|premium
                $table->string('render_mode', 20)->default('overlay'); // overlay|blade
                $table->string('blade_key', 80)->nullable(); // maps to tickets.templates.*
                $table->string('graphic_path', 255)->nullable();
                $table->string('thumbnail_path', 255)->nullable();
                $table->string('accent', 20)->nullable();
                $table->string('accent_soft', 20)->nullable();
                $table->string('header_from', 20)->nullable();
                $table->string('header_to', 20)->nullable();
                $table->string('card_bg', 20)->nullable();
                $table->string('text_color', 20)->nullable();
                $table->string('muted_color', 20)->nullable();
                $table->string('border_color', 20)->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('invitation_design_fields')) {
            Schema::create('invitation_design_fields', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invitation_design_id')->constrained('invitation_designs')->cascadeOnDelete();
                $table->string('field_key', 80);
                $table->string('label', 120);
                $table->string('field_type', 30)->default('text'); // text|textarea
                $table->boolean('is_required')->default(false);
                $table->string('placeholder', 180)->nullable();
                $table->string('default_text', 255)->nullable();
                $table->boolean('maps_to_couple')->default(false); // show when category requires couple
                $table->boolean('show_on_card')->default(true);
                // Overlay positioning (% of graphic box)
                $table->decimal('pos_x', 5, 2)->nullable();
                $table->decimal('pos_y', 5, 2)->nullable();
                $table->decimal('box_width', 5, 2)->nullable();
                $table->unsignedSmallInteger('font_size')->nullable();
                $table->string('font_family', 80)->nullable();
                $table->string('font_weight', 30)->nullable();
                $table->string('font_style', 30)->nullable();
                $table->string('color', 20)->nullable();
                $table->string('text_align', 20)->default('center');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['invitation_design_id', 'field_key']);
            });
        }

        if (Schema::hasTable('events')) {
            if (! Schema::hasColumn('events', 'couple_name_1')) {
                Schema::table('events', function (Blueprint $table) {
                    $table->string('couple_name_1')->nullable();
                });
            }

            if (! Schema::hasColumn('events', 'couple_name_2')) {
                Schema::table('events', function (Blueprint $table) {
                    $table->string('couple_name_2')->nullable();
                });
            }

            if (! Schema::hasColumn('events', 'invitation_design_id')) {
                $afterTicketDesign = Schema::hasColumn('events', 'ticket_design');

                Schema::table('events', function (Blueprint $table) use ($afterTicketDesign) {
                    $column = $table->foreignId('invitation_design_id')->nullable();
                    if ($afterTicketDesign) {
                        $column->after('ticket_design');
                    }
                    $column->constrained('invitation_designs')->nullOnDelete();
                });
            }

            if (! Schema::hasColumn('events', 'invitation_field_values')) {
                $afterCoupleName2 = Schema::hasColumn('events', 'couple_name_2');

                Schema::table('events', function (Blueprint $table) use ($afterCoupleName2) {
                    $column = $table->json('invitation_field_values')->nullable();
                    if ($afterCoupleName2) {
                        $column->after('couple_name_2');
                    }
                });
            }

            if (! Schema::hasColumn('events', 'private_event_category_id')) {
                Schema::table('events', function (Blueprint $table) {
                    $table->unsignedBigInteger('private_event_category_id')->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('events')) {
            Schema::table('events', function (Blueprint $table) {
                if (Schema::hasColumn('events', 'invitation_design_id')) {
                    $table->dropConstrainedForeignId('invitation_design_id');
                }
                if (Schema::hasColumn('events', 'invitation_field_values')) {
                    $table->dropColumn('invitation_field_values');
                }
            });
        }

        Schema::dropIfExists('invitation_design_fields');
        Schema::dropIfExists('invitation_designs');
    }
};
