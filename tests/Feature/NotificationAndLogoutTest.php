<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Tests\TestCase;

class NotificationAndLogoutTest extends TestCase
{
    public function test_html_notification_actions_redirect_and_only_change_the_signed_in_users_rows(): void
    {
        $user = $this->makeUser('first');
        $otherUser = $this->makeUser('other');
        $firstUnread = $this->makeNotification($user, false);
        $secondUnread = $this->makeNotification($user, false);
        $otherUnread = $this->makeNotification($otherUser, false);

        $this->actingAs($user)
            ->from(route('notifications.index'))
            ->post(route('notifications.read-all'))
            ->assertRedirect(route('notifications.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('notifications', ['id' => $firstUnread->id, 'is_read' => true]);
        $this->assertDatabaseHas('notifications', ['id' => $secondUnread->id, 'is_read' => true]);
        $this->assertDatabaseHas('notifications', ['id' => $otherUnread->id, 'is_read' => false]);

        $singleUnread = $this->makeNotification($user, false);
        $this->from(route('notifications.show', $singleUnread->id))
            ->post(route('notifications.read', $singleUnread->id))
            ->assertRedirect(route('notifications.show', $singleUnread->id))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('notifications', ['id' => $singleUnread->id, 'is_read' => true]);

        $ownedToDelete = $this->makeNotification($user, true);
        $this->from(route('notifications.index'))
            ->delete(route('notifications.destroy', $ownedToDelete->id))
            ->assertRedirect(route('notifications.index'))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('notifications', ['id' => $ownedToDelete->id]);
    }

    public function test_ajax_notification_actions_keep_json_and_reject_other_users_notifications(): void
    {
        $user = $this->makeUser('ajax');
        $otherUser = $this->makeUser('ajax-other');
        $owned = $this->makeNotification($user, false);
        $foreign = $this->makeNotification($otherUser, false);

        $this->actingAs($user)
            ->postJson(route('notifications.read-all'))
            ->assertOk()
            ->assertJsonPath('counts.total', 0);

        $this->assertDatabaseHas('notifications', ['id' => $foreign->id, 'is_read' => false]);

        $this->postJson(route('notifications.read', $foreign->id))->assertNotFound();
        $this->assertDatabaseHas('notifications', ['id' => $foreign->id, 'is_read' => false]);
        $this->deleteJson(route('notifications.destroy', $foreign->id))->assertNotFound();
        $this->assertDatabaseHas('notifications', ['id' => $foreign->id]);

        $ajaxUnread = $this->makeNotification($user, false);
        $this->postJson(route('notifications.read', $ajaxUnread->id))
            ->assertOk()
            ->assertJsonPath('counts.total', 0);
        $this->assertDatabaseHas('notifications', ['id' => $ajaxUnread->id, 'is_read' => true]);

        $this->deleteJson(route('notifications.destroy', $owned->id))
            ->assertOk()
            ->assertJsonPath('counts.total', 0);
        $this->assertDatabaseMissing('notifications', ['id' => $owned->id]);
    }

    public function test_logout_forms_confirm_and_logout_invalidates_the_authenticated_session(): void
    {
        $user = $this->makeUser('logout');

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('id="logoutSidebarForm"', false)
            ->assertSee("onclick=\"confirmAction('logoutSidebarForm', 'Keluar dari Sistem', 'Apakah Anda yakin ingin keluar dari web ini?', 'Ya, Keluar', '#dc2626')\"", false)
            ->assertSee('id="logoutDropdownForm"', false)
            ->assertSee("onclick=\"confirmAction('logoutDropdownForm', 'Keluar dari Sistem', 'Apakah Anda yakin ingin keluar dari web ini?', 'Ya, Keluar', '#dc2626')\"", false)
            ->assertDontSee("confirm('Apakah Anda yakin ingin keluar?')", false)
            ->assertSee('method="POST" action="' . route('logout') . '"', false)
            ->assertSee('name="_token"', false);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
    }

    private function makeUser(string $suffix): User
    {
        return User::factory()->create([
            'email' => "{$suffix}@example.test",
            'role' => User::ROLE_SUPER_ADMIN,
            'branch_access_type' => 'all',
            'is_active' => true,
        ]);
    }

    private function makeNotification(User $user, bool $read): Notification
    {
        return Notification::create([
            'notifiable_type' => 'user',
            'notifiable_id' => $user->id,
            'type' => 'system',
            'title' => 'Pemberitahuan test',
            'message' => 'Isi notifikasi untuk pengujian.',
            'is_read' => $read,
            'read_at' => $read ? now() : null,
        ]);
    }
}
