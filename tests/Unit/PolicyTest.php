<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Todo;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Comment;
use App\Models\SavedSearch;
use App\Models\Team;
use App\Models\ExportTemplate;
use App\Models\DashboardWidget;
use App\Policies\TodoPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\TagPolicy;
use App\Policies\CommentPolicy;
use App\Policies\SavedSearchPolicy;
use App\Policies\TeamPolicy;
use App\Policies\ExportTemplatePolicy;
use App\Policies\DashboardWidgetPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use RefreshDatabase;

    // ========================================
    // TodoPolicy Tests
    // ========================================

    public function test_TodoPolicy_自分のTodoは閲覧できる()
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);
        $policy = new TodoPolicy();

        $this->assertTrue($policy->view($user, $todo));
    }

    public function test_TodoPolicy_他人のTodoは閲覧できない()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $otherUser->id]);
        $policy = new TodoPolicy();

        $this->assertFalse($policy->view($user, $todo));
    }

    public function test_TodoPolicy_自分のTodoは更新できる()
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);
        $policy = new TodoPolicy();

        $this->assertTrue($policy->update($user, $todo));
    }

    public function test_TodoPolicy_他人のTodoは更新できない()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $otherUser->id]);
        $policy = new TodoPolicy();

        $this->assertFalse($policy->update($user, $todo));
    }

    public function test_TodoPolicy_自分のTodoは削除できる()
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);
        $policy = new TodoPolicy();

        $this->assertTrue($policy->delete($user, $todo));
    }

    public function test_TodoPolicy_他人のTodoは削除できない()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $otherUser->id]);
        $policy = new TodoPolicy();

        $this->assertFalse($policy->delete($user, $todo));
    }

    // ========================================
    // CategoryPolicy Tests
    // ========================================

    public function test_CategoryPolicy_自分のCategoryは閲覧できる()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);
        $policy = new CategoryPolicy();

        $this->assertTrue($policy->view($user, $category));
    }

    public function test_CategoryPolicy_他人のCategoryは閲覧できない()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $otherUser->id]);
        $policy = new CategoryPolicy();

        $this->assertFalse($policy->view($user, $category));
    }

    public function test_CategoryPolicy_自分のCategoryは更新できる()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);
        $policy = new CategoryPolicy();

        $this->assertTrue($policy->update($user, $category));
    }

    public function test_CategoryPolicy_他人のCategoryは更新できない()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $otherUser->id]);
        $policy = new CategoryPolicy();

        $this->assertFalse($policy->update($user, $category));
    }

    public function test_CategoryPolicy_自分のCategoryは削除できる()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);
        $policy = new CategoryPolicy();

        $this->assertTrue($policy->delete($user, $category));
    }

    public function test_CategoryPolicy_他人のCategoryは削除できない()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $otherUser->id]);
        $policy = new CategoryPolicy();

        $this->assertFalse($policy->delete($user, $category));
    }

    // ========================================
    // TagPolicy Tests
    // ========================================

    public function test_TagPolicy_自分のTagは閲覧できる()
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id]);
        $policy = new TagPolicy();

        $this->assertTrue($policy->view($user, $tag));
    }

    public function test_TagPolicy_他人のTagは閲覧できない()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $otherUser->id]);
        $policy = new TagPolicy();

        $this->assertFalse($policy->view($user, $tag));
    }

    public function test_TagPolicy_自分のTagは更新できる()
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id]);
        $policy = new TagPolicy();

        $this->assertTrue($policy->update($user, $tag));
    }

    public function test_TagPolicy_他人のTagは更新できない()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $otherUser->id]);
        $policy = new TagPolicy();

        $this->assertFalse($policy->update($user, $tag));
    }

    public function test_TagPolicy_自分のTagは削除できる()
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $user->id]);
        $policy = new TagPolicy();

        $this->assertTrue($policy->delete($user, $tag));
    }

    public function test_TagPolicy_他人のTagは削除できない()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $tag = Tag::factory()->create(['user_id' => $otherUser->id]);
        $policy = new TagPolicy();

        $this->assertFalse($policy->delete($user, $tag));
    }

    // ========================================
    // CommentPolicy Tests
    // ========================================

    public function test_CommentPolicy_自分のCommentは閲覧できる()
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'todo_id' => $todo->id
        ]);
        $policy = new CommentPolicy();

        $this->assertTrue($policy->view($user, $comment));
    }

    public function test_CommentPolicy_他人のCommentは閲覧できない()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $otherUser->id]);
        $comment = Comment::factory()->create([
            'user_id' => $otherUser->id,
            'todo_id' => $todo->id
        ]);
        $policy = new CommentPolicy();

        $this->assertFalse($policy->view($user, $comment));
    }

    public function test_CommentPolicy_自分のCommentは更新できる()
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'todo_id' => $todo->id
        ]);
        $policy = new CommentPolicy();

        $this->assertTrue($policy->update($user, $comment));
    }

    public function test_CommentPolicy_他人のCommentは更新できない()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $otherUser->id]);
        $comment = Comment::factory()->create([
            'user_id' => $otherUser->id,
            'todo_id' => $todo->id
        ]);
        $policy = new CommentPolicy();

        $this->assertFalse($policy->update($user, $comment));
    }

    public function test_CommentPolicy_自分のCommentは削除できる()
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'todo_id' => $todo->id
        ]);
        $policy = new CommentPolicy();

        $this->assertTrue($policy->delete($user, $comment));
    }

    public function test_CommentPolicy_他人のCommentは削除できない()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $otherUser->id]);
        $comment = Comment::factory()->create([
            'user_id' => $otherUser->id,
            'todo_id' => $todo->id
        ]);
        $policy = new CommentPolicy();

        $this->assertFalse($policy->delete($user, $comment));
    }

    // ========================================
    // SavedSearchPolicy Tests
    // ========================================

    public function test_SavedSearchPolicy_自分のSavedSearchは閲覧できる()
    {
        $user = User::factory()->create();
        $savedSearch = SavedSearch::factory()->create(['user_id' => $user->id]);
        $policy = new SavedSearchPolicy();

        $this->assertTrue($policy->view($user, $savedSearch));
    }

    public function test_SavedSearchPolicy_他人のSavedSearchは閲覧できない()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $savedSearch = SavedSearch::factory()->create(['user_id' => $otherUser->id]);
        $policy = new SavedSearchPolicy();

        $this->assertFalse($policy->view($user, $savedSearch));
    }

    public function test_SavedSearchPolicy_自分のSavedSearchは更新できる()
    {
        $user = User::factory()->create();
        $savedSearch = SavedSearch::factory()->create(['user_id' => $user->id]);
        $policy = new SavedSearchPolicy();

        $this->assertTrue($policy->update($user, $savedSearch));
    }

    public function test_SavedSearchPolicy_他人のSavedSearchは更新できない()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $savedSearch = SavedSearch::factory()->create(['user_id' => $otherUser->id]);
        $policy = new SavedSearchPolicy();

        $this->assertFalse($policy->update($user, $savedSearch));
    }

    public function test_SavedSearchPolicy_自分のSavedSearchは削除できる()
    {
        $user = User::factory()->create();
        $savedSearch = SavedSearch::factory()->create(['user_id' => $user->id]);
        $policy = new SavedSearchPolicy();

        $this->assertTrue($policy->delete($user, $savedSearch));
    }

    public function test_SavedSearchPolicy_他人のSavedSearchは削除できない()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $savedSearch = SavedSearch::factory()->create(['user_id' => $otherUser->id]);
        $policy = new SavedSearchPolicy();

        $this->assertFalse($policy->delete($user, $savedSearch));
    }

    // ========================================
    // TeamPolicy Tests
    // ========================================

    public function test_TeamPolicy_全ユーザーがチーム一覧を閲覧できる()
    {
        $user = User::factory()->create();
        $policy = new TeamPolicy();

        $this->assertTrue($policy->viewAny($user));
    }

    public function test_TeamPolicy_全ユーザーがチームを作成できる()
    {
        $user = User::factory()->create();
        $policy = new TeamPolicy();

        $this->assertTrue($policy->create($user));
    }

    public function test_TeamPolicy_メンバーはチームを閲覧できる()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->users()->attach($user->id, ['role' => 'member']);
        $policy = new TeamPolicy();

        $this->assertTrue($policy->view($user, $team));
    }

    public function test_TeamPolicy_非メンバーはチームを閲覧できない()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $policy = new TeamPolicy();

        $this->assertFalse($policy->view($user, $team));
    }

    public function test_TeamPolicy_Ownerはチームを更新できる()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->users()->attach($user->id, ['role' => 'owner']);
        $policy = new TeamPolicy();

        $this->assertTrue($policy->update($user, $team));
    }

    public function test_TeamPolicy_Adminはチームを更新できる()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->users()->attach($user->id, ['role' => 'admin']);
        $policy = new TeamPolicy();

        $this->assertTrue($policy->update($user, $team));
    }

    public function test_TeamPolicy_Memberはチームを更新できない()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->users()->attach($user->id, ['role' => 'member']);
        $policy = new TeamPolicy();

        $this->assertFalse($policy->update($user, $team));
    }

    public function test_TeamPolicy_Ownerのみチームを削除できる()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->users()->attach($user->id, ['role' => 'owner']);
        $policy = new TeamPolicy();

        $this->assertTrue($policy->delete($user, $team));
    }

    public function test_TeamPolicy_AdminはチームTodoを作成できる()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->users()->attach($user->id, ['role' => 'admin']);
        $policy = new TeamPolicy();

        $this->assertTrue($policy->createTeamTodo($user, $team));
    }

    public function test_TeamPolicy_Memberは自分のチームTodoを編集できる()
    {
        Event::fake(); // Reverb依存回避

        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->users()->attach($user->id, ['role' => 'member']);
        $todo = Todo::factory()->create(['user_id' => $user->id, 'team_id' => $team->id]);
        $policy = new TeamPolicy();

        $this->assertTrue($policy->updateTeamTodo($user, $team, $todo));
    }

    public function test_TeamPolicy_Ownerは他人のチームTodoを編集できる()
    {
        Event::fake(); // Reverb依存回避

        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();
        $team->users()->attach($owner->id, ['role' => 'owner']);
        $team->users()->attach($member->id, ['role' => 'member']);
        $todo = Todo::factory()->create(['user_id' => $member->id, 'team_id' => $team->id]);
        $policy = new TeamPolicy();

        $this->assertTrue($policy->updateTeamTodo($owner, $team, $todo));
    }

    // ========================================
    // ExportTemplatePolicy Tests
    // ========================================

    public function test_ExportTemplatePolicy_自分のテンプレートは閲覧できる()
    {
        $user = User::factory()->create();
        $template = ExportTemplate::factory()->create(['user_id' => $user->id]);
        $policy = new ExportTemplatePolicy();

        $this->assertTrue($policy->view($user, $template));
    }

    public function test_ExportTemplatePolicy_他人のテンプレートは閲覧できない()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $template = ExportTemplate::factory()->create(['user_id' => $otherUser->id]);
        $policy = new ExportTemplatePolicy();

        $this->assertFalse($policy->view($user, $template));
    }

    public function test_ExportTemplatePolicy_自分のテンプレートは更新できる()
    {
        $user = User::factory()->create();
        $template = ExportTemplate::factory()->create(['user_id' => $user->id]);
        $policy = new ExportTemplatePolicy();

        $this->assertTrue($policy->update($user, $template));
    }

    public function test_ExportTemplatePolicy_他人のテンプレートは更新できない()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $template = ExportTemplate::factory()->create(['user_id' => $otherUser->id]);
        $policy = new ExportTemplatePolicy();

        $this->assertFalse($policy->update($user, $template));
    }

    public function test_ExportTemplatePolicy_自分のテンプレートは削除できる()
    {
        $user = User::factory()->create();
        $template = ExportTemplate::factory()->create(['user_id' => $user->id]);
        $policy = new ExportTemplatePolicy();

        $this->assertTrue($policy->delete($user, $template));
    }

    public function test_ExportTemplatePolicy_他人のテンプレートは削除できない()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $template = ExportTemplate::factory()->create(['user_id' => $otherUser->id]);
        $policy = new ExportTemplatePolicy();

        $this->assertFalse($policy->delete($user, $template));
    }

    // ========================================
    // DashboardWidgetPolicy Tests
    // ========================================

    public function test_DashboardWidgetPolicy_自分のウィジェットは閲覧できる()
    {
        $user = User::factory()->create();
        $widget = DashboardWidget::factory()->create(['user_id' => $user->id]);
        $policy = new DashboardWidgetPolicy();

        $this->assertTrue($policy->view($user, $widget));
    }

    public function test_DashboardWidgetPolicy_他人のウィジェットは閲覧できない()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $widget = DashboardWidget::factory()->create(['user_id' => $otherUser->id]);
        $policy = new DashboardWidgetPolicy();

        $this->assertFalse($policy->view($user, $widget));
    }

    public function test_DashboardWidgetPolicy_自分のウィジェットは更新できる()
    {
        $user = User::factory()->create();
        $widget = DashboardWidget::factory()->create(['user_id' => $user->id]);
        $policy = new DashboardWidgetPolicy();

        $this->assertTrue($policy->update($user, $widget));
    }

    public function test_DashboardWidgetPolicy_他人のウィジェットは更新できない()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $widget = DashboardWidget::factory()->create(['user_id' => $otherUser->id]);
        $policy = new DashboardWidgetPolicy();

        $this->assertFalse($policy->update($user, $widget));
    }

    public function test_DashboardWidgetPolicy_自分のウィジェットは削除できる()
    {
        $user = User::factory()->create();
        $widget = DashboardWidget::factory()->create(['user_id' => $user->id]);
        $policy = new DashboardWidgetPolicy();

        $this->assertTrue($policy->delete($user, $widget));
    }

    public function test_DashboardWidgetPolicy_他人のウィジェットは削除できない()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $widget = DashboardWidget::factory()->create(['user_id' => $otherUser->id]);
        $policy = new DashboardWidgetPolicy();

        $this->assertFalse($policy->delete($user, $widget));
    }
}
