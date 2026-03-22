import { db } from '@db/auth/db'
import type { UserOut } from '@db/auth/types'
import type { PathParams } from 'msw'
import { HttpResponse, http } from 'msw'

// Handlers for auth
export const handlerAuth = [

  http.post<PathParams>(('/api/auth/login'), async ({ request }) => {
    const { login, password } = await request.json() as { login: string; password: string }

    let errors: Record<string, string[]> = {
      login: ['Something went wrong'],
    }

    const loginValue = String(login ?? '').trim().toLowerCase()

    const user = db.users.find(u => {
      const byUsername = u.username.toLowerCase() === loginValue
      const byPhone = (u.phone ?? '').toLowerCase() === loginValue

      return (byUsername || byPhone) && u.password === password
    })

    if (user) {
      try {
        const accessToken = db.userTokens[user.id]

        // We are duplicating user here
        const userData = { ...user }

        const userOutData = Object.fromEntries(
          Object.entries(userData)
            .filter(
              ([key, _]) => !(key === 'password' || key === 'abilityRules'),
            ),
        ) as UserOut['userData']

        const response: UserOut = {
          userAbilityRules: userData.abilityRules,
          accessToken,
          userData: userOutData,
        }

        return HttpResponse.json(response,
          { status: 201 })
      }
      catch (e: unknown) {
        errors = { login: [e as string] }
      }
    }
    else {
      errors = { login: ['Invalid username/phone or password'] }
    }

    return HttpResponse.json({ errors }, { status: 400 })
  }),
]
