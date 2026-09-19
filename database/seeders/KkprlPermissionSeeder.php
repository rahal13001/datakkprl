<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class KkprlPermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'Review:KkprlProposal',
            'RequestEdit:KkprlProposal',
            'ApproveEdit:KkprlProposal',
            'RunEditSession:KkprlProposal',
            'Download:KkprlProposalDocument',
            'Download:KkprlProposalAttachment',
        ] as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }
}
