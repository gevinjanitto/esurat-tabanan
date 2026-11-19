<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShareLinkItem extends Model
{
    protected $fillable = ['share_link_id', 'doc_type', 'doc_id'];

    public function document()
    {
        return $this->doc_type === 'keluar'
            ? SuratKeluar::find($this->doc_id)
            : SuratMasuk::find($this->doc_id);
    }
}
