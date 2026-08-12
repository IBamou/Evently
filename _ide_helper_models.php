<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * A single AI copilot generation attempt: the validated inputs, the queued
 * execution status, and the result once the job finishes.
 *
 * @property int $id
 * @property int $user_id
 * @property string $operation
 * @property AiGenerationStatus $status
 * @property array $inputs
 * @property array|null $result
 * @property string|null $error_message
 * @method static \Illuminate\Database\Eloquent\Builder<static> query()
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\AiGenerationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiGeneration newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiGeneration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiGeneration whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiGeneration whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiGeneration whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiGeneration whereInputs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiGeneration whereOperation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiGeneration whereResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiGeneration whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiGeneration whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiGeneration whereUserId($value)
 */
	class AiGeneration extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $event_id
 * @property string $reference
 * @property BookingStatus $status
 * @property string $subtotal
 * @property string $fees
 * @property string $total
 * @property string $currency
 * @property Carbon|null $expires_at
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $cancelled_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string|null $idempotency_key
 * @property-read \App\Models\Event|null $event
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BookingItem> $items
 * @property-read int|null $items_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment> $payments
 * @property-read int|null $payments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Ticket> $tickets
 * @property-read int|null $tickets_count
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\BookingFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereCancelledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereFees($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereIdempotencyKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking whereUserId($value)
 */
	class Booking extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $booking_id
 * @property int|null $ticket_type_id
 * @property string $ticket_name
 * @property string $unit_price
 * @property int $quantity
 * @property string $line_total
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read \App\Models\Booking $booking
 * @property-read \App\Models\TicketType|null $ticketType
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Ticket> $tickets
 * @property-read int|null $tickets_count
 * @method static \Database\Factories\BookingItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingItem whereBookingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingItem whereLineTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingItem whereTicketName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingItem whereTicketTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingItem whereUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BookingItem whereUpdatedAt($value)
 */
	class BookingItem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $published_count Computed via withCount query alias.
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $image
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Event> $events
 * @property-read int|null $events_count
 * @method static \Database\Factories\CategoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereUpdatedAt($value)
 */
	class Category extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $category_id
 * @property int $organizer_id
 * @property string $title
 * @property string $slug
 * @property string $description
 * @property string $location
 * @property string $city
 * @property EventFormat $format
 * @property EventStatus $status
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property string|null $banner_url
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Booking> $bookings
 * @property-read int|null $bookings_count
 * @property-read \App\Models\Category $category
 * @property-read string $category_gradient
 * @property-read \App\Models\User $organizer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TicketType> $ticketTypes
 * @property-read int|null $ticket_types_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Ticket> $tickets
 * @property-read int|null $tickets_count
 * @method static \Database\Factories\EventFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event published()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereBannerUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereFormat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereOrganizerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereStartsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event withoutTrashed()
 */
	class Event extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $booking_id
 * @property string $provider
 * @property string|null $provider_reference
 * @property PaymentStatus $status
 * @property string $amount
 * @property string $currency
 * @property Carbon|null $paid_at
 * @property array $metadata
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read \App\Models\Booking $booking
 * @method static \Database\Factories\PaymentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereBookingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereProviderReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereUpdatedAt($value)
 */
	class Payment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $booking_id
 * @property int|null $booking_item_id
 * @property int|null $ticket_type_id
 * @property int $user_id
 * @property int $event_id
 * @property string $code
 * @property TicketStatus $status
 * @property Carbon|null $issued_at
 * @property Carbon|null $checked_in_at
 * @property Carbon|null $cancelled_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read \App\Models\Booking $booking
 * @property-read \App\Models\BookingItem|null $bookingItem
 * @property-read \App\Models\User|null $checkedInBy
 * @property-read \App\Models\Event|null $event
 * @property-read \App\Models\TicketType|null $ticketType
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\TicketFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereBookingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereBookingItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereCancelledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereCheckedInAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereCheckedInBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereIssuedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereTicketTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ticket whereUserId($value)
 */
	class Ticket extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $event_id
 * @property string $name
 * @property string|null $description
 * @property string $price
 * @property string $currency
 * @property int $quantity
 * @property int $min_per_booking
 * @property int $max_per_booking
 * @property Carbon|null $sales_start_at
 * @property Carbon|null $sales_end_at
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BookingItem> $bookingItems
 * @property-read int|null $booking_items_count
 * @property-read \App\Models\Event|null $event
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Ticket> $tickets
 * @property-read int|null $tickets_count
 * @method static \Database\Factories\TicketTypeFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType whereMaxPerBooking($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType whereMinPerBooking($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType whereSalesEndAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType whereSalesStartAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TicketType withoutTrashed()
 */
	class TicketType extends \Eloquent {}
}

namespace App\Models{
/**
 * The platform user.
 *
 * @property UserRole $role
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AiGeneration> $aiGenerations
 * @property-read int|null $ai_generations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Booking> $bookings
 * @property-read int|null $bookings_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Event> $events
 * @property-read int|null $events_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Ticket> $tickets
 * @property-read int|null $tickets_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 */
	class User extends \Eloquent implements \Illuminate\Contracts\Auth\MustVerifyEmail {}
}

