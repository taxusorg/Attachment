<?php
use Illuminate\Database\Seeder;

class AttachmentsSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('attachments')->insert([
            'name' => 'file.txt',
            'type' => 'text',
            'format' => 'txt',
            'size' => '',
            'path' => '',
            'author_id' => '',
            'descrip' => 'test'
        ]);
        
        DB::table('attachments')->insert([
            'name' => 'file.jpg',
            'type' => 'image',
            'format' => 'jpg',
            'size' => '',
            'path' => '',
            'author_id' => '',
            'descrip' => 'test'
        ]);
        
    }
}
