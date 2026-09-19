<?php

namespace Tests\Unit\Kkprl;

use App\Domain\Kkprl\ProposalFieldCatalog;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProposalFieldCatalogTest extends TestCase
{
    #[Test]
    public function catalog_covers_template_identity_activity_and_location_fields(): void
    {
        $fields = ProposalFieldCatalog::fields('bag-1');

        $this->assertContains('applicant_name', $fields);
        $this->assertContains('institution_name', $fields);
        $this->assertContains('identity_number', $fields);
        $this->assertContains('tax_number', $fields);
        $this->assertContains('supporting_activity', $fields);
        $this->assertContains('workforce_male', $fields);
        $this->assertContains('workforce_female', $fields);
        $this->assertContains('investment_unit', $fields);
        $this->assertContains('site_plan_description', $fields);
        $this->assertContains('land_relation', $fields);
        $this->assertContains('has_existing_permits', $fields);
    }

    #[Test]
    public function conditional_catalogs_are_separate_and_explicit(): void
    {
        $this->assertContains('material_volume_unit', ProposalFieldCatalog::fields('bag-4'));
        $this->assertContains('land_status', ProposalFieldCatalog::fields('bag-5-land'));
        $this->assertContains('permit_validity', ProposalFieldCatalog::fields('bag-5-permits'));
        $this->assertSame('Nama pemohon', ProposalFieldCatalog::label('applicant_name'));
    }
}
