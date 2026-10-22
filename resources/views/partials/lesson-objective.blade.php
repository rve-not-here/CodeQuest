{{--
    The lesson's objective prose. Kept in one place so the reading column can
    fold it into a disclosure while a Knowledge Check is open, without
    duplicating the markup.
--}}
<section class="atlas-lesson-section" aria-labelledby="lesson-objective">
    <h2 id="lesson-objective">What you will build</h2>
    <div class="atlas-lesson-prose">
        @foreach ($paragraphs as $paragraph)
            <p>{{ $paragraph }}</p>
        @endforeach
    </div>
</section>
