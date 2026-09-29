<div class="mt-4">
    <label for="password" class="block text-sm font-medium text-gray-700">新しいパスワード</label>
    <input id="password" name="password" type="password" required autocomplete="new-password"
           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
    <p class="mt-1 text-xs text-gray-500">8文字以上で、大文字・小文字・数字・記号をそれぞれ1つ以上含めてください。</p>
    @error('password')<div class="text-red-500 text-xs mt-1">{{ $message }}</div>@enderror
</div>
<div class="mt-4">
    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">新しいパスワード（確認）</label>
    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
</div>
