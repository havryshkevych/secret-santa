<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Game;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ParticipantSecurityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_with_telegram_id_sees_only_their_own_participant_data()
    {
        // Create two users with different telegram_ids
        $user1 = User::factory()->create([
            'telegram_id' => 111111,
            'telegram_username' => 'alice',
        ]);

        $user2 = User::factory()->create([
            'telegram_id' => 222222,
            'telegram_username' => 'bob',
        ]);

        // Create a game
        $game = Game::factory()->create();

        // Create participants for both users
        $participant1 = Participant::factory()->create([
            'game_id' => $game->id,
            'telegram_chat_id' => 111111,
            'telegram_username' => 'alice',
            'wishlist_text' => 'Alice wishlist',
            'shipping_address' => 'Alice address',
        ]);

        $participant2 = Participant::factory()->create([
            'game_id' => $game->id,
            'telegram_chat_id' => 222222,
            'telegram_username' => 'bob',
            'wishlist_text' => 'Bob wishlist',
            'shipping_address' => 'Bob address',
        ]);

        // User1 should see only their data
        $response1 = $this->actingAs($user1)
            ->get(route('game.join', $game->join_token));

        $response1->assertOk();
        $content1 = $response1->getContent();
        $this->assertStringContainsString('Alice wishlist', $content1);
        $this->assertStringContainsString('Alice address', $content1);
        $this->assertStringNotContainsString('Bob wishlist', $content1);
        $this->assertStringNotContainsString('Bob address', $content1);

        // User2 should see only their data
        $response2 = $this->actingAs($user2)
            ->get(route('game.join', $game->join_token));

        $response2->assertOk();
        $content2 = $response2->getContent();
        $this->assertStringContainsString('Bob wishlist', $content2);
        $this->assertStringContainsString('Bob address', $content2);
        $this->assertStringNotContainsString('Alice wishlist', $content2);
        $this->assertStringNotContainsString('Alice address', $content2);
    }

    /** @test */
    public function user_without_telegram_id_does_not_see_participants_with_telegram_chat_id()
    {
        // User without telegram_id (only username)
        $user = User::factory()->create([
            'telegram_id' => null,
            'telegram_username' => 'charlie',
        ]);

        $game = Game::factory()->create();

        // Participant with telegram_chat_id (different user who started bot)
        $otherParticipant = Participant::factory()->create([
            'game_id' => $game->id,
            'telegram_chat_id' => 333333,
            'telegram_username' => 'charlie', // Same username!
            'wishlist_text' => 'Other Charlie wishlist',
            'shipping_address' => 'Other Charlie address',
        ]);

        // User should NOT see the other participant's data
        $response = $this->actingAs($user)
            ->get(route('game.join', $game->join_token));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringNotContainsString('Other Charlie wishlist', $content);
        $this->assertStringNotContainsString('Other Charlie address', $content);
    }

    /** @test */
    public function user_can_only_update_their_own_wishlist()
    {
        $user1 = User::factory()->create([
            'telegram_id' => 111111,
            'telegram_username' => 'alice',
        ]);

        $user2 = User::factory()->create([
            'telegram_id' => 222222,
            'telegram_username' => 'bob',
        ]);

        $game = Game::factory()->create();

        $participant1 = Participant::factory()->create([
            'game_id' => $game->id,
            'telegram_chat_id' => 111111,
            'telegram_username' => 'alice',
            'wishlist_text' => 'Original Alice wishlist',
        ]);

        $participant2 = Participant::factory()->create([
            'game_id' => $game->id,
            'telegram_chat_id' => 222222,
            'telegram_username' => 'bob',
            'wishlist_text' => 'Original Bob wishlist',
        ]);

        // User1 tries to update Bob's wishlist by manipulating the participant ID
        $this->actingAs($user1)
            ->post(route('game.updateMyWishlist'), [
                'wishlists' => [
                    $participant2->id => 'Hacked wishlist',
                ],
            ]);

        // Bob's wishlist should NOT be changed
        $this->assertDatabaseHas('participants', [
            'id' => $participant2->id,
            'wishlist_text' => 'Original Bob wishlist',
        ]);

        // User1 can update their own wishlist
        $this->actingAs($user1)
            ->post(route('game.updateMyWishlist'), [
                'wishlists' => [
                    $participant1->id => 'Updated Alice wishlist',
                ],
            ]);

        $this->assertDatabaseHas('participants', [
            'id' => $participant1->id,
            'wishlist_text' => 'Updated Alice wishlist',
        ]);
    }

    /** @test */
    public function user_can_only_update_their_own_shipping_address()
    {
        $user1 = User::factory()->create([
            'telegram_id' => 111111,
            'telegram_username' => 'alice',
        ]);

        $user2 = User::factory()->create([
            'telegram_id' => 222222,
            'telegram_username' => 'bob',
        ]);

        $game = Game::factory()->create();

        $participant1 = Participant::factory()->create([
            'game_id' => $game->id,
            'telegram_chat_id' => 111111,
            'telegram_username' => 'alice',
            'shipping_address' => 'Alice original address',
        ]);

        $participant2 = Participant::factory()->create([
            'game_id' => $game->id,
            'telegram_chat_id' => 222222,
            'telegram_username' => 'bob',
            'shipping_address' => 'Bob original address',
        ]);

        // User1 updates their shipping address
        $this->actingAs($user1)
            ->post(route('game.updateMyWishlist'), [
                'shipping_address' => 'Alice new address',
            ]);

        // Only Alice's participant should be updated
        $this->assertDatabaseHas('participants', [
            'id' => $participant1->id,
            'shipping_address' => 'Alice new address',
        ]);

        // Bob's address should remain unchanged
        $this->assertDatabaseHas('participants', [
            'id' => $participant2->id,
            'shipping_address' => 'Bob original address',
        ]);
    }

    /** @test */
    public function user_without_telegram_id_cannot_update_participants_with_telegram_chat_id()
    {
        // User without telegram_id
        $user = User::factory()->create([
            'telegram_id' => null,
            'telegram_username' => 'charlie',
        ]);

        $game = Game::factory()->create();

        // Participant with same username but has telegram_chat_id
        $participant = Participant::factory()->create([
            'game_id' => $game->id,
            'telegram_chat_id' => 333333,
            'telegram_username' => 'charlie',
            'shipping_address' => 'Original address',
        ]);

        // User tries to update
        $this->actingAs($user)
            ->post(route('game.updateMyWishlist'), [
                'shipping_address' => 'Hacked address',
            ]);

        // Participant's address should NOT be changed
        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'shipping_address' => 'Original address',
        ]);
    }

    /** @test */
    public function user_can_only_leave_their_own_game()
    {
        $user1 = User::factory()->create(['telegram_id' => 111111]);
        $user2 = User::factory()->create(['telegram_id' => 222222]);

        $game = Game::factory()->create(['is_started' => false]);

        $participant1 = Participant::factory()->create([
            'game_id' => $game->id,
            'telegram_chat_id' => 111111,
        ]);

        $participant2 = Participant::factory()->create([
            'game_id' => $game->id,
            'telegram_chat_id' => 222222,
        ]);

        // User1 leaves the game
        $this->actingAs($user1)
            ->delete(route('game.leave', $game));

        // Only participant1 should be deleted
        $this->assertDatabaseMissing('participants', ['id' => $participant1->id]);
        $this->assertDatabaseHas('participants', ['id' => $participant2->id]);
    }
}
