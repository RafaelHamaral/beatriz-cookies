<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutSection extends Model
{
    protected $fillable = ['title', 'body', 'image_path', 'image_mime', 'image_alt', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'images' => 'array'];
    }

    public function gallery(): array
    {
        $images = $this->images ?? [];
        if ($this->image_path) {
            $images = ['primary' => ['path' => $this->image_path, 'mime' => $this->image_mime]] + $images;
        }

        return $images;
    }

    public static function current(): self
    {
        return static::query()->find(1) ?? new static([
            'title' => 'Sobre a Beatriz & Kathy',
            'body' => 'Preparamos cookies artesanais e doces gourmet em pequenas fornadas, com cuidado em cada detalhe. Conheça nossa vitrine e escolha um sabor para tornar seu dia mais doce.',
            'is_active' => true,
        ]);
    }
}
