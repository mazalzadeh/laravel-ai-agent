<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Document;
use App\Models\DocumentChunk;

class DocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Document::factory()
                ->count(5)
                ->create()
                ->each(function(Document $document)){
                    DocumentChunk::factory()
                            ->count(rand(2,5))
                            ->create(['document_id'=>$document->id]);
                });
    }
}
