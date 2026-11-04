<?php

namespace Tests\Unit\Enums;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\AttachmentType;
use App\Enums\BookingStatus;
use App\Enums\CampaignStatus;
use App\Enums\CancelledBy;
use App\Enums\PaymentStatus;
use App\Enums\PetSize;
use App\Enums\PetStatus;
use App\Enums\Role;
use App\Enums\Sex;
use App\Enums\ShelterMemberRole;
use App\Enums\ShelterStatus;
use App\Enums\Species;
use App\Enums\Tone;
use App\Enums\WalkSlotState;
use App\Enums\WalkSlotStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StatusEnumsTest extends TestCase
{
    /**
     * @return array<string, array{class-string}>
     */
    public static function enums(): array
    {
        return array_map(fn (string $class) => [$class], [
            'Role' => Role::class,
            'ShelterMemberRole' => ShelterMemberRole::class,
            'ShelterStatus' => ShelterStatus::class,
            'Species' => Species::class,
            'Sex' => Sex::class,
            'PetSize' => PetSize::class,
            'PetStatus' => PetStatus::class,
            'ApplicationType' => ApplicationType::class,
            'ApplicationStatus' => ApplicationStatus::class,
            'CampaignStatus' => CampaignStatus::class,
            'PaymentStatus' => PaymentStatus::class,
            'WalkSlotStatus' => WalkSlotStatus::class,
            'WalkSlotState' => WalkSlotState::class,
            'BookingStatus' => BookingStatus::class,
            'CancelledBy' => CancelledBy::class,
            'AttachmentType' => AttachmentType::class,
        ]);
    }

    /**
     * @param  class-string<\BackedEnum>  $enum
     */
    #[DataProvider('enums')]
    public function test_every_case_has_russian_label_and_options(string $enum): void
    {
        foreach ($enum::cases() as $case) {
            $this->assertMatchesRegularExpression('/\p{Cyrillic}/u', $case->label(), $enum.'::'.$case->name);

            if (method_exists($case, 'tone')) {
                $this->assertInstanceOf(Tone::class, $case->tone());
            }
        }

        $this->assertSame(array_column($enum::cases(), 'value'), $enum::values());
        $this->assertSame($enum::values(), array_keys($enum::options()));
    }

    public function test_labels_and_tones_match_design_tokens(): void
    {
        // docs/design/tokens.md, «Подписи статусов»
        $this->assertSame('Ищет дом', PetStatus::LookingForHome->label());
        $this->assertSame(Tone::Warning, PetStatus::OnTreatment->tone());
        $this->assertSame('Идёт сбор', CampaignStatus::Active->label());
        $this->assertSame(Tone::Tag, CampaignStatus::Active->tone());
        $this->assertSame('Ждёт подтверждения', BookingStatus::Pending->label());
        $this->assertSame(Tone::Meadow, BookingStatus::Completed->tone());
        $this->assertSame('Проверенный приют', ShelterStatus::Approved->label());
        $this->assertTrue(ShelterStatus::Blocked->isSolid());
        $this->assertTrue(PetStatus::Draft->isDashed());
        $this->assertSame('Срок оплаты истёк', PaymentStatus::Expired->label());
    }

    public function test_status_groups(): void
    {
        $this->assertSame([BookingStatus::Pending, BookingStatus::Confirmed], BookingStatus::active());
        $this->assertFalse(BookingStatus::Completed->isActive());
        $this->assertTrue(ApplicationStatus::Approved->isActive());
        $this->assertFalse(ApplicationStatus::Withdrawn->isActive());
        $this->assertNotContains(PetStatus::Draft, PetStatus::public());
        $this->assertNotContains(PetStatus::Adopted, PetStatus::catalog());
        $this->assertContains(PetStatus::Adopted, PetStatus::public());
        $this->assertNotContains(PetStatus::Adopted, PetStatus::walkable());
        $this->assertNotContains(CampaignStatus::Draft, CampaignStatus::public());
        $this->assertTrue(WalkSlotState::FewLeft->isBookable());
        $this->assertFalse(WalkSlotState::Full->isBookable());
    }
}
