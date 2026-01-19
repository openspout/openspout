<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Comment;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class CommentTest extends TestCase
{
    public function testCommentWithHeight(): void
    {
        $comment = new Comment(height: '50pt');
        $newComment = $comment->withHeight('100pt');

        self::assertSame('100pt', $newComment->height);
        self::assertSame('50pt', $comment->height);
    }

    public function testCommentWithWidth(): void
    {
        $comment = new Comment(width: '80pt');
        $newComment = $comment->withWidth('120pt');

        self::assertSame('120pt', $newComment->width);
        self::assertSame('80pt', $comment->width);
    }

    public function testCommentWithMarginLeft(): void
    {
        $comment = new Comment(marginLeft: '10pt');
        $newComment = $comment->withMarginLeft('20pt');

        self::assertSame('20pt', $newComment->marginLeft);
        self::assertSame('10pt', $comment->marginLeft);
    }

    public function testCommentWithMarginTop(): void
    {
        $comment = new Comment(marginTop: '5pt');
        $newComment = $comment->withMarginTop('15pt');

        self::assertSame('15pt', $newComment->marginTop);
        self::assertSame('5pt', $comment->marginTop);
    }

    public function testCommentWithVisible(): void
    {
        $comment = new Comment(visible: false);
        $newComment = $comment->withVisible(true);

        self::assertTrue($newComment->visible);
        self::assertFalse($comment->visible);
    }

    public function testCommentWithFillColor(): void
    {
        $comment = new Comment(fillColor: '#FFFFFF');
        $newComment = $comment->withFillColor('#000000');

        self::assertSame('#000000', $newComment->fillColor);
        self::assertSame('#FFFFFF', $comment->fillColor);
    }

    public function testCommentWithTextRuns(): void
    {
        $textRun1 = new TextRun('Hello');
        $textRun2 = new TextRun('World');
        $comment = new Comment(textRuns: [$textRun1]);
        $newComment = $comment->withTextRuns([$textRun2]);

        self::assertCount(1, $newComment->textRuns);
        self::assertSame('World', $newComment->textRuns[0]->text);
        self::assertSame('Hello', $comment->textRuns[0]->text);
    }
}
